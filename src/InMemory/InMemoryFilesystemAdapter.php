<?php

declare(strict_types=1);

namespace League\Flysystem\InMemory;

use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\Visibility;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;

use function array_keys;
use function rtrim;
use function str_starts_with;
use function strlen;
use function strpos;

class InMemoryFilesystemAdapter implements FilesystemAdapter
{
    const DUMMY_FILE_FOR_FORCED_LISTING_IN_FLYSYSTEM_TEST = '______DUMMY_FILE_FOR_FORCED_LISTING_IN_FLYSYSTEM_TEST';

    /**
     * @var InMemoryFile[]
     */
    private $files = [];

    /**
     * @var string
     */
    private $defaultVisibility;

    /**
     * @var MimeTypeDetector
     */
    private $mimeTypeDetector;

    public function __construct(string $defaultVisibility = Visibility::PUBLIC, MimeTypeDetector $mimeTypeDetector = null)
    {
        $this->defaultVisibility = $defaultVisibility;
        $this->mimeTypeDetector = $mimeTypeDetector ?: new FinfoMimeTypeDetector();
    }

    public function fileExists(string $path): bool
    {
        return array_key_exists($this->preparePath($path), $this->files);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $path = $this->preparePath($path);
        $file = $this->files[$path] = $this->files[$path] ?? new InMemoryFile();
        $file->updateContents($contents, $config->get('timestamp'));

        $visibility = $config->get(Config::OPTION_VISIBILITY, $this->defaultVisibility);
        $file->setVisibility($visibility);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, (string) stream_get_contents($contents), $config);
    }

    public function read(string $path): string
    {
        $path = $this->preparePath($path);

        if (array_key_exists($path, $this->files) === false) {
            throw UnableToReadFile::fromLocation($path, 'file does not exist');
        }

        return $this->files[$path]->read();
    }

    public function readStream(string $path)
    {
        $path = $this->preparePath($path);

        if (array_key_exists($path, $this->files) === false) {
            throw UnableToReadFile::fromLocation($path, 'file does not exist');
        }

        return $this->files[$path]->readStream();
    }

    public function delete(string $path): void
    {
        unset($this->files[$this->preparePath($path)]);
    }

    public function deleteDirectory(string $prefix): void
    {
        $prefix = $this->preparePath($prefix);
        $prefix = rtrim($prefix, '/') . '/';

        foreach (array_keys($this->files) as $path) {
            if (strpos($path, $prefix) === 0) {
                unset($this->files[$path]);
            }
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        $filePath = rtrim($path, '/') . '/' . self::DUMMY_FILE_FOR_FORCED_LISTING_IN_FLYSYSTEM_TEST;
        $this->write($filePath, '', $config);
    }

    public function directoryExists(string $path): bool
    {
        $prefix = $this->preparePath($path);
        $prefix = rtrim($prefix, '/') . '/';

        foreach (array_keys($this->files) as $path) {
            if (strpos($path, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $path = $this->preparePath($path);

        if (array_key_exists($path, $this->files) === false) {
            throw UnableToSetVisibility::atLocation($path, 'file does not exist');
        }

        $this->files[$path]->setVisibility($visibility);
    }

    public function visibility(string $path): FileAttributes
    {
        $path = $this->preparePath($path);

        if (array_key_exists($path, $this->files) === false) {
            throw UnableToRetrieveMetadata::visibility($path, 'file does not exist');
        }

        return new FileAttributes($path, null, $this->files[$path]->visibility());
    }

    public function mimeType(string $path): FileAttributes
    {
        $preparedPath = $this->preparePath($path);

        if (array_key_exists($preparedPath, $this->files) === false) {
            throw UnableToRetrieveMetadata::mimeType($path, 'file does not exist');
        }

        $mimeType = $this->mimeTypeDetector->detectMimeType($path, $this->files[$preparedPath]->read());

        if ($mimeType === null) {
            throw UnableToRetrieveMetadata::mimeType($path);
        }

        return new FileAttributes($preparedPath, null, null, null, $mimeType);
    }

    public function lastModified(string $path): FileAttributes
    {
        $path = $this->preparePath($path);

        if (array_key_exists($path, $this->files) === false) {
            throw UnableToRetrieveMetadata::lastModified($path, 'file does not exist');
        }

        return new FileAttributes($path, null, null, $this->files[$path]->lastModified());
    }

    public function fileSize(string $path): FileAttributes
    {
        $path = $this->preparePath($path);

        if (array_key_exists($path, $this->files) === false) {
            throw UnableToRetrieveMetadata::fileSize($path, 'file does not exist');
        }

        return new FileAttributes($path, $this->files[$path]->fileSize());
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $prefix = rtrim($this->preparePath($path), '/') . '/';
        $prefixLength = strlen($prefix);
        $listedDirectories = [];

        foreach ($this->files as $path => $file) {
            if (substr($path, 0, $prefixLength) === $prefix) {
                $subPath = substr($path, $prefixLength);
                $dirname = dirname($subPath);

                if ($dirname !== '.') {
                    $parts = explode('/', $dirname);
                    $dirPath = '';

                    foreach ($parts as $index => $part) {
                        if ($deep === false && $index >= 1) {
                            break;
                        }

                        $dirPath .= $part . '/';

                        if ( ! in_array($dirPath, $listedDirectories)) {
                            $listedDirectories[] = $dirPath;
                            yield new DirectoryAttributes(trim($prefix . $dirPath, '/'));
                        }
                    }
                }

                $dummyFilename = self::DUMMY_FILE_FOR_FORCED_LISTING_IN_FLYSYSTEM_TEST;
                if (substr($path, -strlen($dummyFilename)) === $dummyFilename) {
                    continue;
                }

                if ($deep === true || strpos($subPath, '/') === false) {
                    yield new FileAttributes(ltrim($path, '/'), $file->fileSize(), $file->visibility(), $file->lastModified(), $file->mimeType());
                }
            }
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $source = $this->preparePath($source);
        $destination = $this->preparePath($destination);

        if ($this->fileExists($destination) || $this->directoryExists($destination)) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, new \RuntimeException("Destination already exist"));
        }

        try {
            $this->copy($source, $destination, $config);
        } catch (UnableToCopyFile $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }

        if ($this->fileExists($source)) {
            $this->delete($source);

            return;
        } elseif ($this->directoryExists($source)) {
            $this->deleteDirectory($source);

            return;
        }

        throw UnableToMoveFile::fromLocationTo($source, $destination);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $source = $this->preparePath($source);
        $destination = $this->preparePath($destination);

        $lastModified = $config->get('timestamp', time());
        if ($this->fileExists($source)) {
            $this->files[$destination] = $this->files[$source]->withLastModified($lastModified);

            return;
        } elseif ($this->directoryExists($source)) {
            $sourcePrefix = rtrim($source, '/') . '/';
            $destinationPrefix = rtrim($destination, '/') . '/';

            $sourcePrefixLength = strlen($source) + 1;

            foreach (array_keys($this->files) as $path) {
                if (str_starts_with($path, $sourcePrefix)) {
                    $newPath = $destinationPrefix . substr($path, $sourcePrefixLength);

                    $this->files[$newPath] = $this->files[$path]->withLastModified($lastModified);
                }
            }

            return;
        }

        throw UnableToCopyFile::fromLocationTo($source, $destination, new \RuntimeException("Source does not exist"));
    }

    private function preparePath(string $path): string
    {
        return '/' . ltrim($path, '/');
    }

    public function deleteEverything(): void
    {
        $this->files = [];
    }
}
