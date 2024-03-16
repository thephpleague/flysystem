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

class InMemoryFilesystemAdapter implements FilesystemAdapter
{
    public const DUMMY_FILE_FOR_FORCED_LISTING_IN_FLYSYSTEM_TEST = '______DUMMY_FILE_FOR_FORCED_LISTING_IN_FLYSYSTEM_TEST';

    /**
     * @var InMemoryFile[]
     */
    private array $files = [];
    private MimeTypeDetector $mimeTypeDetector;

    public function __construct(
        private string $defaultVisibility = Visibility::PUBLIC,
        ?MimeTypeDetector $mimeTypeDetector = null
    ) {
        $this->mimeTypeDetector = $mimeTypeDetector ?? new FinfoMimeTypeDetector();
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

    public function deleteDirectory(string $path): void
    {
        $path = $this->preparePath($path);
        $path = rtrim($path, '/') . '/';

        foreach (array_keys($this->files) as $filePath) {
            if (str_starts_with($filePath, $path)) {
                unset($this->files[$filePath]);
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
        $path = $this->preparePath($path);
        $path = rtrim($path, '/') . '/';

        foreach (array_keys($this->files) as $filePath) {
            if (str_starts_with($filePath, $path)) {
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

        foreach ($this->files as $filePath => $file) {
            if (str_starts_with($filePath, $prefix)) {
                $subPath = substr($filePath, $prefixLength);
                $dirname = dirname($subPath);

                if ($dirname !== '.') {
                    $parts = explode('/', $dirname);
                    $dirPath = '';

                    foreach ($parts as $index => $part) {
                        if ($deep === false && $index >= 1) {
                            break;
                        }

                        $dirPath .= $part . '/';

                        if ( ! in_array($dirPath, $listedDirectories, true)) {
                            $listedDirectories[] = $dirPath;
                            yield new DirectoryAttributes(trim($prefix . $dirPath, '/'));
                        }
                    }
                }

                $dummyFilename = self::DUMMY_FILE_FOR_FORCED_LISTING_IN_FLYSYSTEM_TEST;
                if (str_ends_with($filePath, $dummyFilename)) {
                    continue;
                }

                if ($deep === true || ! str_contains($subPath, '/')) {
                    yield new FileAttributes(ltrim($filePath, '/'), $file->fileSize(), $file->visibility(), $file->lastModified(), $file->mimeType());
                }
            }
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $sourcePath = $this->preparePath($source);
        $destinationPath = $this->preparePath($destination);

        if ( ! $this->fileExists($source) || $this->fileExists($destination)) {
            throw UnableToMoveFile::fromLocationTo($source, $destination);
        }

        $this->files[$destinationPath] = $this->files[$sourcePath];
        unset($this->files[$sourcePath]);

        if ($visibility = $config->get(Config::OPTION_VISIBILITY)) {
            $this->setVisibility($destination, $visibility);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $source = $this->preparePath($source);
        $destination = $this->preparePath($destination);

        if ( ! $this->fileExists($source)) {
            throw UnableToCopyFile::fromLocationTo($source, $destination);
        }

        $lastModified = $config->get('timestamp', time());
        $this->files[$destination] = $this->files[$source]->withLastModified($lastModified);

        if ($visibility = $config->get(Config::OPTION_VISIBILITY)) {
            $this->setVisibility($destination, $visibility);
        }
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
