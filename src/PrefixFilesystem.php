<?php

namespace League\Flysystem;

class PrefixFilesystem implements FilesystemOperator
{
    protected FilesystemOperator $filesystem;
    private string $prefix;

    /**
     * @internal
     */
    public function __construct(FilesystemOperator $filesystem, string $prefix)
    {
        if (empty($prefix)) {
            throw new \InvalidArgumentException('The prefix must not be empty.');
        }

        $this->filesystem = $filesystem;
        $this->prefix = trim($prefix, '/') . '/';
    }

    public function has(string $location): bool
    {
        $location = $this->preparePath($location);

        return $this->filesystem->has($location);
    }

    public function read(string $location): string
    {
        $location = $this->preparePath($location);

        return $this->filesystem->read($location);
    }

    public function readStream(string $location)
    {
        $location = $this->preparePath($location);

        return $this->filesystem->readStream($location);
    }

    public function listContents(string $location, bool $deep = self::LIST_SHALLOW): DirectoryListing
    {
        $location = $this->preparePath($location);

        return new DirectoryListing(array_map(
            function (StorageAttributes $info) {
                if ($info instanceof DirectoryAttributes) {
                    return new DirectoryAttributes(
                        $this->stripPath($info->path()),
                        $info->visibility(),
                        $info->lastModified(),
                        $info->extraMetadata()
                    );
                }

                if ($info instanceof FileAttributes) {
                    return new FileAttributes(
                        $this->stripPath($info->path()),
                        $info->fileSize(),
                        $info->visibility(),
                        $info->lastModified(),
                        $info->mimeType(),
                        $info->extraMetadata()
                    );
                }

                return $info;
            },
            $this->filesystem->listContents($location, $deep)->toArray()
        ));
    }

    public function fileExists(string $location): bool
    {
        $location = $this->preparePath($location);

        return $this->filesystem->fileExists($location);
    }

    public function directoryExists(string $location): bool
    {
        $location = $this->preparePath($location);

        return $this->filesystem->directoryExists($location);
    }

    public function lastModified(string $path): int
    {
        $path = $this->preparePath($path);

        return $this->filesystem->lastModified($path);
    }

    public function fileSize(string $path): int
    {
        $path = $this->preparePath($path);

        return $this->filesystem->fileSize($path);
    }

    public function mimeType(string $path): string
    {
        $path = $this->preparePath($path);

        return $this->filesystem->mimeType($path);
    }

    public function visibility(string $path): string
    {
        $path = $this->preparePath($path);

        return $this->filesystem->visibility($path);
    }

    public function write(string $location, string $contents, array $config = []): void
    {
        $location = $this->preparePath($location);

        $this->filesystem->write($location, $contents, $config);
    }

    public function writeStream(string $location, $contents, array $config = []): void
    {
        $location = $this->preparePath($location);

        $this->filesystem->writeStream($location, $contents, $config);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $path = $this->preparePath($path);

        $this->filesystem->setVisibility($path, $visibility);
    }

    public function delete(string $location): void
    {
        $location = $this->preparePath($location);

        $this->filesystem->delete($location);
    }

    public function deleteDirectory(string $location): void
    {
        $location = $this->preparePath($location);

        $this->filesystem->deleteDirectory($location);
    }

    public function createDirectory(string $location, array $config = []): void
    {
        $location = $this->preparePath($location);

        $this->filesystem->createDirectory($location, $config);
    }

    public function move(string $source, string $destination, array $config = []): void
    {
        $source = $this->preparePath($source);
        $destination = $this->preparePath($destination);

        $this->filesystem->move($source, $destination, $config);
    }

    public function copy(string $source, string $destination, array $config = []): void
    {
        $source = $this->preparePath($source);
        $destination = $this->preparePath($destination);

        $this->filesystem->copy($source, $destination, $config);
    }

    private function stripPath(string $path): string
    {
        $prefix = rtrim($this->prefix, '/');
        $path = preg_replace('#^' . preg_quote($prefix, '#') . '#', '', $path);

        return ltrim($path, '/');
    }

    private function preparePath(string $path): string
    {
        return $this->prefix . $path;
    }
}
