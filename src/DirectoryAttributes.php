<?php

declare(strict_types=1);

namespace League\Flysystem;

class DirectoryAttributes implements StorageAttributes
{
    /**
     * @var string
     */
    private $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function type(): string
    {
        return StorageAttributes::TYPE_DIRECTORY;
    }
}
