<?php

declare(strict_types=1);

namespace League\Flysystem;

interface Prefixer
{
    public function prefixPath(string $path): string;

    public function stripPrefix(string $path): string;

    public function stripDirectoryPrefix(string $path): string;

    public function prefixDirectoryPath(string $path): string;
}
