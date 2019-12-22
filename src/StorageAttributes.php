<?php

declare(strict_types=1);

namespace League\Flysystem;

interface StorageAttributes
{
    const TYPE_FILE = 'FILE';
    const TYPE_DIRECTORY = 'DIRECTORY';

    public function path(): string;

    public function type(): string;

    public function visibility(): ?string;
}
