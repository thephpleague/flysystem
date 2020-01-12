<?php

declare(strict_types=1);

namespace League\Flysystem;

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

    public function write(string $location, string $contents, array $config = []): void
    {
        $this->adapter->write(
            $this->pathNormalizer->normalizePath($location),
            $contents,
            $this->config->extend($config)
        );
    }

    public function writeStream(string $location, $contents, array $config = []): void
    {
        $this->assertIsResource($contents);
        $this->rewindStream($contents);
        $this->adapter->writeStream(
            $this->pathNormalizer->normalizePath($location),
            $contents,
            $this->config->extend($config)
        );
    }

    public function update(string $location, string $contents, array $config = []): void
    {
        $this->adapter->update(
            $this->pathNormalizer->normalizePath($location),
            $contents,
            $this->config->extend($config)
        );
    }

    public function updateStream(string $location, $contents, array $config = []): void
    {
        $this->assertIsResource($contents);
        $this->rewindStream($contents);
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

    public function createDirectory(string $location, array $config = []): void
    {
        $this->adapter->createDirectory(
            $this->pathNormalizer->normalizePath($location),
            $this->config->extend($config)
        );
    }

    public function listContents(string $location, bool $recursive = false): DirectoryListing
    {
        return new DirectoryListing($this->adapter->listContents($location, $recursive));
    }

    public function move(string $source, string $destination, array $config = []): void
    {
        $this->adapter->move(
            $this->pathNormalizer->normalizePath($source),
            $this->pathNormalizer->normalizePath($destination),
            $this->config->extend($config)
        );
    }

    public function copy(string $source, string $destination, array $config = []): void
    {
        $this->adapter->copy(
            $this->pathNormalizer->normalizePath($source),
            $this->pathNormalizer->normalizePath($destination),
            $this->config->extend($config)
        );
    }

    public function lastModified(string $path): int
    {
        return $this->adapter->lastModified($path)->lastModified();
    }

    public function fileSize(string $path): int
    {
        return $this->adapter->fileSize($path)->fileSize();
    }

    public function mimeType(string $path): string
    {
        return $this->adapter->mimeType($path)->mimeType();
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $this->adapter->setVisibility($path, $visibility);
    }

    public function visibility(string $path): string
    {
        return $this->adapter->visibility($path)->visibility();
    }

    private function assertIsResource($contents): void
    {
        if ( ! is_resource($contents)) {
            throw new InvalidArgumentException(
                "Invalid stream provided, expected resource, received " . gettype($contents)
            );
        }
    }

    private function rewindStream($resource): void
    {
        if (ftell($resource) !== 0 && stream_get_meta_data($resource)['seekable']) {
            rewind($resource);
        }
    }
}
