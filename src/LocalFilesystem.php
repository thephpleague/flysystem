<?php

declare(strict_types=1);

namespace League\Flysystem;

use Generator;

class LocalFilesystem implements Filesystem
{
    public function __construct(string $location)
    {
    }

    public function write(string $location, string $contents, Config $config): void
    {
    }

    public function writeStream(string $location, $contents, Config $config): void
    {
    }

    public function update(string $location, string $contents, Config $config): void
    {
    }

    public function updateStream(string $location, $contents, Config $config): void
    {
    }

    public function delete(string $location): void
    {
    }

    public function deleteDirectory(string $prefix): void
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

    public function read(string $location): string
    {
    }

    /**
     * @inheritDoc
     */
    public function readStream(string $location)
    {
    }
}
