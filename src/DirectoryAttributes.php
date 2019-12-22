<?php

declare(strict_types=1);

namespace League\Flysystem;

class DirectoryAttributes implements StorageAttributes
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string|null
     */
    private $visibility;

    public function __construct(string $path, ?string $visibility = null)
    {
        $this->path = $path;
        $this->visibility = $visibility;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function type(): string
    {
        return StorageAttributes::TYPE_DIRECTORY;
    }

    public function visibility(): ?string
    {
        return $this->visibility;
    }
}
