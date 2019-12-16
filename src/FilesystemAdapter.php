<?php

declare(strict_types=1);

namespace League\Flysystem;

use Generator;

interface FilesystemAdapter
{
    public function fileExists(string $location): bool;

    public function write(string $location, string $contents, Config $config): void;

    public function writeStream(string $location, $contents, Config $config): void;

    public function update(string $location, string $contents, Config $config): void;

    public function updateStream(string $location, $contents, Config $config): void;

    public function read(string $location): string;

    /**
     * @param string $location
     * @return resource
     */
    public function readStream(string $location);

    public function delete(string $location): void;

    public function deleteDirectory(string $location): void;

    public function createDirectory(string $location, Config $config): void;

    public function setVisibility(string $location, string $visibility): void;

    public function listContents(string $location): Generator;

    public function rename(string $source, string $destination): void;

    public function copy(string $source, string $destination): void;
}
