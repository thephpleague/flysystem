<?php

namespace League\Flysystem\PathPrefixing;

use Generator;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use Throwable;

class PathPrefixedAdapter implements FilesystemAdapter
{
    protected FilesystemAdapter $adapter;
    private PathPrefixer $prefix;

    public function __construct(FilesystemAdapter $adapter, string $prefix)
    {
        if ($prefix === '') {
            throw new \InvalidArgumentException('The prefix must not be empty.');
        }

        $this->adapter = $adapter;
        $this->prefix = new PathPrefixer($prefix);
    }

    public function read(string $location): string
    {
        try {
            return $this->adapter->read($this->prefix->prefixPath($location));
        } catch (Throwable $previous) {
            throw UnableToReadFile::fromLocation($location, $previous->getMessage(), $previous);
        }
    }

    public function readStream(string $location)
    {
        try {
            return $this->adapter->readStream($this->prefix->prefixPath($location));
        } catch (Throwable $previous) {
            throw UnableToReadFile::fromLocation($location, $previous->getMessage(), $previous);
        }
    }

    public function listContents(string $location, bool $deep): Generator
    {
        foreach ($this->adapter->listContents($this->prefix->prefixPath($location), $deep) as $attributes) {
            yield $attributes->withPath($this->prefix->stripPrefix($attributes->path()));
        }
    }

    public function fileExists(string $location): bool
    {
        try {
            return $this->adapter->fileExists($this->prefix->prefixPath($location));
        } catch (Throwable $previous) {
            throw UnableToCheckFileExistence::forLocation($location, $previous);
        }
    }

    public function directoryExists(string $location): bool
    {
        try {
            return $this->adapter->directoryExists($this->prefix->prefixPath($location));
        } catch (Throwable $previous) {
            throw UnableToCheckDirectoryExistence::forLocation($location, $previous);
        }
    }

    public function lastModified(string $path): FileAttributes
    {
        try {
            return $this->adapter->lastModified($this->prefix->prefixPath($path));
        } catch (Throwable $previous) {
            throw UnableToRetrieveMetadata::lastModified($path, $previous->getMessage(), $previous);
        }
    }

    public function fileSize(string $path): FileAttributes
    {
        try {
            return $this->adapter->fileSize($this->prefix->prefixPath($path));
        } catch (Throwable $previous) {
            throw UnableToRetrieveMetadata::fileSize($path, $previous->getMessage(), $previous);
        }
    }

    public function mimeType(string $path): FileAttributes
    {
        try {
            return $this->adapter->mimeType($this->prefix->prefixPath($path));
        } catch (Throwable $previous) {
            throw UnableToRetrieveMetadata::mimeType($path, $previous->getMessage(), $previous);
        }
    }

    public function visibility(string $path): FileAttributes
    {
        try {
            return $this->adapter->visibility($this->prefix->prefixPath($path));
        } catch (Throwable $previous) {
            throw UnableToRetrieveMetadata::visibility($path, $previous->getMessage(), $previous);
        }
    }

    public function write(string $location, string $contents, Config $config): void
    {
        try {
            $this->adapter->write($this->prefix->prefixPath($location), $contents, $config);
        } catch (Throwable $previous) {
            throw UnableToWriteFile::atLocation($location, $previous->getMessage(), $previous);
        }
    }

    public function writeStream(string $location, $contents, Config $config): void
    {
        try {
            $this->adapter->writeStream($this->prefix->prefixPath($location), $contents, $config);
        } catch (Throwable $previous) {
            throw UnableToWriteFile::atLocation($location, $previous->getMessage(), $previous);
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        try {
            $this->adapter->setVisibility($this->prefix->prefixPath($path), $visibility);
        } catch (Throwable $previous) {
            throw UnableToSetVisibility::atLocation($path, $previous->getMessage(), $previous);
        }
    }

    public function delete(string $location): void
    {
        try {
            $this->adapter->delete($this->prefix->prefixPath($location));
        } catch (Throwable $previous) {
            throw UnableToDeleteFile::atLocation($location, $previous->getMessage(), $previous);
        }
    }

    public function deleteDirectory(string $location): void
    {
        try {
            $this->adapter->deleteDirectory($this->prefix->prefixPath($location));
        } catch (Throwable $previous) {
            throw UnableToDeleteDirectory::atLocation($location, $previous->getMessage(), $previous);
        }
    }

    public function createDirectory(string $location, Config $config): void
    {
        try {
            $this->adapter->createDirectory($this->prefix->prefixPath($location), $config);
        } catch (Throwable $previous) {
            throw UnableToCreateDirectory::atLocation($location, $previous->getMessage(), $previous);
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->adapter->move($this->prefix->prefixPath($source), $this->prefix->prefixPath($destination), $config);
        } catch (Throwable $previous) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $previous);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $this->adapter->copy($this->prefix->prefixPath($source), $this->prefix->prefixPath($destination), $config);
        } catch (Throwable $previous) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $previous);
        }
    }
}
