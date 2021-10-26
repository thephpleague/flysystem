<?php

declare(strict_types=1);

namespace League\Flysystem;

final class PrefixedFilesystem implements FilesystemOperator
{
    private $filesystem;
    private $prefixer;

    public function __construct(FilesystemOperator $filesystem, string $prefix)
    {
        $this->filesystem = $filesystem;
        $this->prefixer = new PathPrefixer($prefix);
    }

    public function fileExists(string $location): bool
    {
        return $this->filesystem->fileExists($this->prefixer->prefixPath($location));
    }

    public function read(string $location): string
    {
        return $this->filesystem->read($this->prefixer->prefixPath($location));
    }

    public function readStream(string $location)
    {
        return $this->filesystem->readStream($this->prefixer->prefixPath($location));
    }

    public function listContents(string $location, bool $deep = self::LIST_SHALLOW): DirectoryListing
    {
        return $this->filesystem->listContents($this->prefixer->prefixPath($location), $deep);
    }

    public function lastModified(string $path): int
    {
        return $this->filesystem->lastModified($this->prefixer->prefixPath($path));
    }

    public function fileSize(string $path): int
    {
        return $this->filesystem->fileSize($this->prefixer->prefixPath($path));
    }

    public function mimeType(string $path): string
    {
        return $this->filesystem->mimeType($this->prefixer->prefixPath($path));
    }

    public function visibility(string $path): string
    {
        return $this->filesystem->visibility($this->prefixer->prefixPath($path));
    }

    public function write(string $location, string $contents, array $config = []): void
    {
        $this->filesystem->write($this->prefixer->prefixPath($location), $contents, $config);
    }

    public function writeStream(string $location, $contents, array $config = []): void
    {
        $this->filesystem->writeStream($this->prefixer->prefixPath($location), $contents, $config);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $this->filesystem->setVisibility($this->prefixer->prefixPath($path), $visibility);
    }

    public function delete(string $location): void
    {
        $this->filesystem->delete($this->prefixer->prefixPath($location));
    }

    public function deleteDirectory(string $location): void
    {
        $this->filesystem->deleteDirectory($this->prefixer->prefixPath($location));
    }

    public function createDirectory(string $location, array $config = []): void
    {
        $this->filesystem->createDirectory($this->prefixer->prefixPath($location), $config);
    }

    public function move(string $source, string $destination, array $config = []): void
    {
        $this->filesystem->move(
            $this->prefixer->prefixPath($source),
            $this->prefixer->prefixPath($destination),
            $config
        );
    }

    public function copy(string $source, string $destination, array $config = []): void
    {
        $this->filesystem->copy(
            $this->prefixer->prefixPath($source),
            $this->prefixer->prefixPath($destination),
            $config
        );
    }
}
