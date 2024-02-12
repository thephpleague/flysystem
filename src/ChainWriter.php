<?php

declare(strict_types=1);

namespace League\Flysystem;

class ChainWriter implements FilesystemWriter
{
    /**
     * @var FilesystemWriter[]
     */
    private array $filesystems;

    /**
     * @param FilesystemWriter[] $filesystems
     */
    public function __construct(array $filesystems = [])
    {
        $this->filesystems = $filesystems;
    }

    public function addFilesystem(FilesystemWriter $filesystem)
    {
        $this->filesystems[] = $filesystem;
    }

    /**
     * @return FilesystemWriter[]
     */
    public function getFilesystems(): array
    {
        return $this->filesystems;
    }

    public function write(string $location, string $contents, array $config = []): void
    {
        foreach ($this->filesystems as $filesystem) {
            $filesystem->write($location, $contents, $config);
        }
    }

    public function writeStream(string $location, $contents, array $config = []): void
    {
        foreach ($this->filesystems as $filesystem) {
            $filesystem->writeStream($location, $contents, $config);
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        foreach ($this->filesystems as $filesystem) {
            $filesystem->setVisibility($path, $visibility);
        }
    }

    public function delete(string $location): void
    {
        foreach ($this->filesystems as $filesystem) {
            $filesystem->delete($location);
        }
    }

    public function deleteDirectory(string $location): void
    {
        foreach ($this->filesystems as $filesystem) {
            $filesystem->deleteDirectory($location);
        }
    }

    public function createDirectory(string $location, array $config = []): void
    {
        foreach ($this->filesystems as $filesystem) {
            $filesystem->createDirectory($location, $config);
        }
    }

    public function move(string $source, string $destination, array $config = []): void
    {
        foreach ($this->filesystems as $filesystem) {
            $filesystem->move($source, $destination, $config);
        }
    }

    public function copy(string $source, string $destination, array $config = []): void
    {
        foreach ($this->filesystems as $filesystem) {
            $filesystem->copy($source, $destination, $config);
        }
    }
}
