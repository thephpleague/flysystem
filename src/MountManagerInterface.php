<?php

declare(strict_types=1);

namespace League\Flysystem;

interface MountManagerInterface
{
    public function mountFilesystem(string $key, FilesystemOperator $filesystem): void;

    public function isFileSystemExists(string $key): bool;
}
