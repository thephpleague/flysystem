<?php

declare(strict_types=1);

namespace League\Flysystem;

interface StorageAttributes
{
    public const TYPE_FILE = 'file';
    public const TYPE_DIRECTORY = 'dir';

    public function path(): string;

    public function type(): string;

    public function visibility(): ?string;
}
