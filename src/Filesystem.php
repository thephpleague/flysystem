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

    /**
     * @var PathNormalizer
     */
    private $pathNormalizer;

    public function __construct(
        FilesystemAdapter $adapter,
        array $config = [],
        PathNormalizer $pathNormalizer = null
    ) {
        $this->adapter = $adapter;
        $this->config = new Config($config);
        $this->pathNormalizer = $pathNormalizer ?: new WhitespacePathNormalizer();
    }

    public function fileExists(string $location): bool
    {
        return $this->adapter->fileExists($this->pathNormalizer->normalizePath($location));
    }

    public function write(string $location, string $contents, array $config): void
    {
        $this->adapter->write(
            $this->pathNormalizer->normalizePath($location),
            $contents,
            $this->config->extend($config)
        );
    }

    public function writeStream(string $location, $contents, array $config): void
    {
        $this->adapter->writeStream(
            $this->pathNormalizer->normalizePath($location),
            $contents,
            $this->config->extend($config)
        );
    }

    public function update(string $location, string $contents, array $config): void
    {
        $this->adapter->update(
            $this->pathNormalizer->normalizePath($location),
            $contents,
            $this->config->extend($config)
        );
    }

    public function updateStream(string $location, $contents, array $config): void
    {
        $this->adapter->updateStream(
            $this->pathNormalizer->normalizePath($location),
            $contents,
            $this->config->extend($config)
        );
    }

    public function read(string $location): string
    {
        return $this->adapter->read($this->pathNormalizer->normalizePath($location));
    }

    public function readStream(string $location)
    {
        return $this->adapter->readStream($this->pathNormalizer->normalizePath($location));
    }

    public function delete(string $location): void
    {
        $this->adapter->delete($this->pathNormalizer->normalizePath($location));
    }

    public function deleteDirectory(string $location): void
    {
        $this->adapter->deleteDirectory($this->pathNormalizer->normalizePath($location));
    }

    public function createDirectory(string $location, array $config): void
    {
        $this->adapter->createDirectory($this->pathNormalizer->normalizePath($location));
    }

    public function listContents(string $location): Generator
    {
        yield from $this->listContents($location);
    }

    public function move(string $source, string $destination): void
    {
        $this->adapter->move(
            $this->pathNormalizer->normalizePath($source),
            $this->pathNormalizer->normalizePath($destination)
        );
    }

    public function copy(string $source, string $destination): void
    {
    }
}
