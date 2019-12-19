<?php

declare(strict_types=1);

namespace League\Flysystem;

use Generator;

interface FilesystemAdapter
{
    public function fileExists(string $path): bool;

    public function write(string $path, string $contents, Config $config): void;

    public function writeStream(string $path, $contents, Config $config): void;

    public function update(string $path, string $contents, Config $config): void;

    public function updateStream(string $path, $contents, Config $config): void;

    public function read(string $path): string;

    public function readStream(string $path);

    public function delete(string $path): void;

    public function deleteDirectory(string $path): void;

    public function createDirectory(string $path, Config $config): void;

    public function setVisibility(string $path, string $visibility): void;

    public function getVisibility(string $path): string;
    public function getMimeType(string $path): string;
    public function getModifiedTimestamp(string $path): int;
    public function getFileSize(string $path): int;

    public function listContents(string $path): Generator;

    public function rename(string $source, string $destination): void;

    public function copy(string $source, string $destination): void;
}
