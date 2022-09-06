<?php

namespace League\Flysystem;

use Throwable;

class PathPrefixedFilesystem implements FilesystemOperator
{
    protected FilesystemOperator $filesystem;
    private PathPrefixer $prefix;

    /**
     * @internal
     */
    public function __construct(FilesystemOperator $filesystem, string $prefix)
    {
        if (empty($prefix)) {
            throw new \InvalidArgumentException('The prefix must not be empty.');
        }

        $this->filesystem = $filesystem;
        $this->prefix = new PathPrefixer($prefix);
    }

    public function has(string $location): bool
    {
        return $this->filesystem->has($this->preparePath($location));
    }

    public function read(string $location): string
    {
        try {
            return $this->filesystem->read($this->preparePath($location));
        } catch (Throwable $previous) {
            throw UnableToReadFile::fromLocation($location, $previous->getMessage(), $previous);
        }
    }

    public function readStream(string $location)
    {
        try {
            return $this->filesystem->readStream($this->preparePath($location));
        } catch (Throwable $previous) {
            throw UnableToReadFile::fromLocation($location, $previous->getMessage(), $previous);
        }
    }

    public function listContents(string $location, bool $deep = self::LIST_SHALLOW): DirectoryListing
    {
        return $this->filesystem->listContents($this->preparePath($location), $deep)->map(
            fn(StorageAttributes $attributes) => $attributes->withPath($this->stripPath($attributes->path()))
        );
    }

    public function fileExists(string $location): bool
    {
        try {
            return $this->filesystem->fileExists($this->preparePath($location));
        } catch (Throwable $previous) {
            throw UnableToCheckFileExistence::forLocation($location, $previous);
        }
    }

    public function directoryExists(string $location): bool
    {
        try {
            return $this->filesystem->directoryExists($this->preparePath($location));
        } catch (Throwable $previous) {
            throw UnableToCheckDirectoryExistence::forLocation($location, $previous);
        }
    }

    public function lastModified(string $path): int
    {
        try {
            return $this->filesystem->lastModified($this->preparePath($path));
        } catch (Throwable $previous) {
            throw UnableToRetrieveMetadata::lastModified($path, $previous->getMessage(), $previous);
        }
    }

    public function fileSize(string $path): int
    {
        try {
            return $this->filesystem->fileSize($this->preparePath($path));
        } catch (Throwable $previous) {
            throw UnableToRetrieveMetadata::fileSize($path, $previous->getMessage(), $previous);
        }
    }

    public function mimeType(string $path): string
    {
        try {
            return $this->filesystem->mimeType($this->preparePath($path));
        } catch (Throwable $previous) {
            throw UnableToRetrieveMetadata::mimeType($path, $previous->getMessage(), $previous);
        }
    }

    public function visibility(string $path): string
    {
        try {
            return $this->filesystem->visibility($this->preparePath($path));
        } catch (Throwable $previous) {
            throw UnableToRetrieveMetadata::visibility($path, $previous->getMessage(), $previous);
        }
    }

    public function write(string $location, string $contents, array $config = []): void
    {
        try {
            $this->filesystem->write($this->preparePath($location), $contents, $config);
        } catch (Throwable $previous) {
            throw UnableToWriteFile::atLocation($location, $previous->getMessage(), $previous);
        }
    }

    public function writeStream(string $location, $contents, array $config = []): void
    {
        try {
            $this->filesystem->writeStream($this->preparePath($location), $contents, $config);
        } catch (Throwable $previous) {
            throw UnableToWriteFile::atLocation($location, $previous->getMessage(), $previous);
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        try {
            $this->filesystem->setVisibility($this->preparePath($path), $visibility);
        } catch (Throwable $previous) {
            throw UnableToSetVisibility::atLocation($path, $previous->getMessage(), $previous);
        }
    }

    public function delete(string $location): void
    {
        try {
            $this->filesystem->delete($this->preparePath($location));
        } catch (Throwable $previous) {
            throw UnableToDeleteFile::atLocation($location, $previous->getMessage(), $previous);
        }
    }

    public function deleteDirectory(string $location): void
    {
        try {
            $this->filesystem->deleteDirectory($this->preparePath($location));
        } catch (Throwable $previous) {
            throw UnableToDeleteDirectory::atLocation($location, $previous->getMessage(), $previous);
        }
    }

    public function createDirectory(string $location, array $config = []): void
    {
        try {
            $this->filesystem->createDirectory($this->preparePath($location), $config);
        } catch (Throwable $previous) {
            throw UnableToCreateDirectory::atLocation($location, $previous->getMessage(), $previous);
        }
    }

    public function move(string $source, string $destination, array $config = []): void
    {
        try {
            $this->filesystem->move($this->preparePath($source), $this->preparePath($destination), $config);
        } catch (Throwable $previous) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $previous);
        }
    }

    public function copy(string $source, string $destination, array $config = []): void
    {
        try {
            $this->filesystem->copy($this->preparePath($source), $this->preparePath($destination), $config);
        } catch (Throwable $previous) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $previous);
        }
    }

    private function stripPath(string $path): string
    {
        return $this->prefix->stripPrefix($path);
    }

    private function preparePath(string $path): string
    {
        return $this->prefix->prefixPath($path);
    }
}
