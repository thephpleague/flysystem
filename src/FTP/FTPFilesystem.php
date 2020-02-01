<?php

declare(strict_types=1);

namespace League\Flysystem\FTP;

use Generator;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;

class FTPFilesystem implements FilesystemAdapter
{
    public function __construct(FTPConnectionOptions $connectionOptions, FTPConnectionProvider $connectionProvider = null)
    {
    }

    public function fileExists(string $path): bool
    {
    }

    private function dirExists(string $path): bool
    {
    }

    public function write(string $path, string $contents, Config $config): void
    {
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
    }

    public function read(string $path): string
    {
    }

    public function readStream(string $path)
    {
    }

    public function delete(string $path): void
    {
    }

    public function deleteDirectory(string $path): void
    {
    }

    public function createDirectory(string $path, Config $config): void
    {
    }

    public function setVisibility(string $path, $visibility): void
    {
    }

    public function visibility(string $path): FileAttributes
    {
    }

    public function mimeType(string $path): FileAttributes
    {
    }

    public function lastModified(string $path): FileAttributes
    {
    }

    public function fileSize(string $path): FileAttributes
    {
    }

    public function listContents(string $path, bool $recursive): Generator
    {
    }

    public function move(string $source, string $destination, Config $config): void
    {
    }

    public function copy(string $source, string $destination, Config $config): void
    {
    }
}
