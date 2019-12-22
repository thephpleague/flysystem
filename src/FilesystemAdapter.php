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

    public function visibility(string $path): string;
    public function mimeType(string $path): string;
    public function lastModified(string $path): int;
    public function fileSize(string $path): int;

    public function listContents(string $path): Generator;

    public function move(string $source, string $destination): void;

    public function copy(string $source, string $destination): void;
}
