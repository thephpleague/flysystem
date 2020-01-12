<?php

declare(strict_types=1);

namespace League\Flysystem;

class FileAttributes implements StorageAttributes
{
    use ProxyArrayAccessToProperties;

    private $type = StorageAttributes::TYPE_FILE;

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

    /**
     * @var array
     */
    private $extraMetadata;

    public function __construct(
        string $path,
        ?int $fileSize = null,
        ?string $visibility = null,
        ?int $lastModified = null,
        ?string $mimeType = null,
        array $extraMetadata = []
    ) {
        $this->path = $path;
        $this->fileSize = $fileSize;
        $this->visibility = $visibility;
        $this->lastModified = $lastModified;
        $this->mimeType = $mimeType;
        $this->extraMetadata = $extraMetadata;
    }

    public function type(): string
    {
        return $this->type;
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

    public function extraMetadata(): array
    {
        return $this->extraMetadata;
    }

    public static function fromArray(array $attributes): StorageAttributes
    {
        return new static(
            $attributes['path'],
            $attributes['file_size'] ?? null,
            $attributes['visibility'] ?? null,
            $attributes['last_modified'] ?? null,
            $attributes['mime_type'] ?? null,
            $attributes['extra_metadata'] ?? []
        );
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => self::TYPE_FILE,
            'path' => $this->path,
            'file_size' => $this->fileSize,
            'visibility' => $this->visibility,
            'last_modified' => $this->lastModified,
            'mime_type' => $this->mimeType,
            'extra_metadata' => $this->extraMetadata,
        ];
    }
}
