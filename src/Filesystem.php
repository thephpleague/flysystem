<?php

declare(strict_types=1);

namespace League\Flysystem;

use Generator;

class Filesystem
{
    /**
     * @var FilesystemAdapter
     */
    private $adapter;

    /**
     * @var Config
     */
    private $config;

    public function __construct(FilesystemAdapter $adapter, array $config = [])
    {
        $this->adapter = $adapter;
        $this->config = new Config($config);
    }

    public function fileExists(string $location): bool
    {
        return $this->adapter->fileExists($location);
    }

    public function write(string $location, string $contents, array $config): void
    {
    }

    public function writeStream(string $location, $contents, array $config): void
    {
    }

    public function update(string $location, string $contents, array $config): void
    {
    }

    public function updateStream(string $location, $contents, array $config): void
    {
    }

    public function read(string $location): string
    {
    }

    /**
     * @inheritDoc
     */
    public function readStream(string $location)
    {
    }

    public function delete(string $location): void
    {
    }

    public function deleteDirectory(string $location): void
    {
    }

    public function createDirectory(string $location, array $config): void
    {
    }

    public function listContents(string $location): Generator
    {
    }

    public function rename(string $source, string $destination): void
    {
    }

    public function copy(string $source, string $destination): void
    {
    }
}
