<?php

declare(strict_types=1);

namespace League\Flysystem\GoogleCloudStorage;

use Google\Cloud\Core\Exception\NotFoundException;
use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageObject;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use Throwable;

class GoogleCloudStorageAdapter implements FilesystemAdapter
{
    /**
     * @var Bucket
     */
    private $bucket;

    /**
     * @var PathPrefixer
     */
    private $prefixer;

    /**
     * @var VisibilityHandler
     */
    private $visibilityHandler;

    /**
     * @var string
     */
    private $defaultVisibility;

    /**
     * @var MimeTypeDetector
     */
    private $mimeTypeDetector;

    public function __construct(
        Bucket $bucket,
        string $prefix = '',
        VisibilityHandler $visibilityHandler = null,
        string $defaultVisibility = Visibility::PRIVATE,
        MimeTypeDetector $mimeTypeDetector = null
    ) {
        $this->bucket = $bucket;
        $this->prefixer = new PathPrefixer($prefix);
        $this->visibilityHandler = $visibilityHandler ?: new PortableVisibilityHandler();
        $this->defaultVisibility = $defaultVisibility;
        $this->mimeTypeDetector = $mimeTypeDetector ?: new FinfoMimeTypeDetector();
    }

    public function fileExists(string $path): bool
    {
        $prefixedPath = $this->prefixer->prefixPath($path);

        return $this->bucket->object($prefixedPath)->exists();
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->upload($path, $contents, $config);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->upload($path, $contents, $config);
    }

    /**
     * @param resource|string $contents
     */
    private function upload(string $path, $contents, Config $config): void
    {
        $prefixedPath = $this->prefixer->prefixPath($path);
        $options = ['name' => $prefixedPath];

        $visibility = $config->get(Config::OPTION_VISIBILITY, $this->defaultVisibility);
        $predefinedAcl = $this->visibilityHandler->visibilityToPredefinedAcl($visibility);

        if ($predefinedAcl !== PortableVisibilityHandler::NO_PREDEFINED_VISIBILITY) {
            $options['predefinedAcl'] = $predefinedAcl;
        }

        $metadata = $config->get('metadata', []);
        $shouldDetermineMimetype = $contents !== '' && ! array_key_exists('contentType', $metadata);
        if ($shouldDetermineMimetype && $mimeType = $this->mimeTypeDetector->detectMimeType($path, $contents)) {
            $metadata['contentType'] = $mimeType;
        }
        $options['metadata'] = $metadata;

        try {
            $this->bucket->upload($contents, $options);
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($path, '', $exception);
        }
    }

    public function read(string $path): string
    {
        $prefixedPath = $this->prefixer->prefixPath($path);

        try {
            return $this->bucket->object($prefixedPath)->downloadAsString();
        } catch (Throwable $exception) {
            throw UnableToReadFile::fromLocation($path, '', $exception);
        }
    }

    public function readStream(string $path)
    {
        $prefixedPath = $this->prefixer->prefixPath($path);

        try {
            $stream = $this->bucket->object($prefixedPath)
                ->downloadAsStream()
                ->detach();
        } catch (Throwable $exception) {
            throw UnableToReadFile::fromLocation($path, '', $exception);
        }

        // @codeCoverageIgnoreStart
        if ( ! is_resource($stream)) {
            throw UnableToReadFile::fromLocation($path, 'Downloaded object does not contain a file resource.');
        }
        // @codeCoverageIgnoreEnd

        return $stream;
    }

    public function delete(string $path): void
    {
        try {
            $prefixedPath = $this->prefixer->prefixPath($path);
            $this->bucket->object($prefixedPath)->delete();
        } catch (NotFoundException $thisIsOk) {
            // this is ok
        } catch (Throwable $exception) {
            throw UnableToDeleteFile::atLocation($path, '', $exception);
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            /** @var StorageAttributes[] $listing */
            $listing = $this->listContents($path, true);

            foreach ($listing as $attributes) {
                $this->delete($attributes->path());
            }
        } catch (Throwable $exception) {
            throw UnableToDeleteDirectory::atLocation($path, '', $exception);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        $prefixedPath = rtrim($this->prefixer->prefixPath($path), '/') . '/';
        $this->bucket->upload('', ['name' => $prefixedPath]);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        try {
            $prefixedPath = $this->prefixer->prefixPath($path);
            $object = $this->bucket->object($prefixedPath);
            $this->visibilityHandler->setVisibility($object, $visibility);
        } catch (Throwable $previous) {
            throw UnableToSetVisibility::atLocation($path, '', $previous);
        }
    }

    public function visibility(string $path): FileAttributes
    {
        try {
            $prefixedPath = $this->prefixer->prefixPath($path);
            $object = $this->bucket->object($prefixedPath);
            $visibility = $this->visibilityHandler->determineVisibility($object);

            return new FileAttributes($path, null, $visibility);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::visibility($path, '', $exception);
        }
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->fileAttributes($path, 'mimeType');
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->fileAttributes($path, 'lastModified');
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->fileAttributes($path, 'fileSize');
    }

    private function fileAttributes(string $path, string $type): FileAttributes
    {
        $exception = null;
        $prefixedPath = $this->prefixer->prefixPath($path);

        try {
            $object = $this->bucket->object($prefixedPath);
            $fileAttributes = $this->storageObjectToStorageAttributes($object);
        } catch (Throwable $exception) {
            // passthrough
        }

        if ( ! isset($fileAttributes) || ! $fileAttributes instanceof FileAttributes || $fileAttributes[$type] === null) {
            throw UnableToRetrieveMetadata::{$type}($path, '', $exception);
        }

        return $fileAttributes;
    }

    public function storageObjectToStorageAttributes(StorageObject $object): StorageAttributes
    {
        $path = $this->prefixer->stripPrefix($object->name());
        $info = $object->info();
        $lastModified = strtotime($info['updated']);

        if (substr($path, -1, 1) === '/') {
            return new DirectoryAttributes(rtrim($path, '/'), null, $lastModified);
        }

        $fileSize = intval($info['size']);
        $mimeType = $info['contentType'] ?? null;

        return new FileAttributes($path, $fileSize, null, $lastModified, $mimeType, $info);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $prefixedPath = $this->prefixer->prefixPath($path);
        $prefixes = $options = [];

        if ( ! empty($prefixedPath)) {
            $options = ['prefix' => sprintf('%s/', rtrim($prefixedPath, '/'))];
        }

        if ($deep === false) {
            $options['delimiter'] = '/';
            $options['includeTrailingDelimiter'] = true;
        }

        $objects = $this->bucket->objects($options);

        /** @var StorageObject $object */
        foreach ($objects as $object) {
            $prefixes[$this->prefixer->stripDirectoryPrefix($object->name())] = true;
            yield $this->storageObjectToStorageAttributes($object);
        }

        foreach ($objects->prefixes() as $prefix) {
            $prefix = $this->prefixer->stripDirectoryPrefix($prefix);

            if (array_key_exists($prefix, $prefixes)) {
                continue;
            }

            $prefixes[$prefix] = true;
            yield new DirectoryAttributes($prefix);
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->copy($source, $destination, $config);
            $this->delete($source);
        } catch (Throwable $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            /** @var string $visibility */
            $visibility = $this->visibility($source)->visibility();
            $prefixedSource = $this->prefixer->prefixPath($source);
            $options = ['name' => $this->prefixer->prefixPath($destination)];
            $predefinedAcl = $this->visibilityHandler->visibilityToPredefinedAcl($visibility);

            if ($predefinedAcl !== PortableVisibilityHandler::NO_PREDEFINED_VISIBILITY) {
                $options['predefinedAcl'] = $predefinedAcl;
            }

            $this->bucket->object($prefixedSource)->copy($this->bucket, $options);
        } catch (Throwable $previous) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $previous);
        }
    }
}
