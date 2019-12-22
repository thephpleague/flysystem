<?php

declare(strict_types=1);

namespace League\Flysystem;

class FileAttributes implements StorageAttributes
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var int|null
     */
    private $fileSize;

    /**
     * @var string|null
     */
    private $visibility;

    /**
     * @var int|null
     */
    private $lastModified;

    /**
     * @var string|null
     */
    private $mimeType;

    public function __construct(
        string $path,
        ?int $fileSize = null,
        ?string $visibility = null,
        ?int $lastModified = null,
        ?string $mimeType = null
    ) {
        $this->path = $path;
        $this->fileSize = $fileSize;
        $this->visibility = $visibility;
        $this->lastModified = $lastModified;
        $this->mimeType = $mimeType;
    }

    public function type(): string
    {
        return StorageAttributes::TYPE_FILE;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function fileSize(): ?int
    {
        return $this->fileSize;
    }

    public function visibility(): ?string
    {
        return $this->visibility;
    }

    public function lastModified(): ?int
    {
        return $this->lastModified;
    }

    public function mimeType(): ?string
    {
        return $this->mimeType;
    }
}
