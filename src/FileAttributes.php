<?php

declare(strict_types=1);

namespace League\Flysystem;

class FileAttributes implements StorageAttributes
{
    public const ATTRIBUTE_PATH = 'path';
    public const ATTRIBUTE_TYPE = 'type';
    public const ATTRIBUTE_FILE_SIZE = 'file_size';
    public const ATTRIBUTE_VISIBILITY = 'visibility';
    public const ATTRIBUTE_LAST_MODIFIED = 'last_modified';
    public const ATTRIBUTE_MIME_TYPE = 'mime_type';
    public const ATTRIBUTE_EXTRA_METADATA = 'extra_metadata';
    use ProxyArrayAccessToProperties;

    /**
     * @var string
     */
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

    public function isFile(): bool
    {
        return true;
    }

    public function isDir(): bool
    {
        return false;
    }

    public static function fromArray(array $attributes): StorageAttributes
    {
        return new FileAttributes(
            $attributes[self::ATTRIBUTE_PATH],
            $attributes[self::ATTRIBUTE_FILE_SIZE] ?? null,
            $attributes[self::ATTRIBUTE_VISIBILITY] ?? null,
            $attributes[self::ATTRIBUTE_LAST_MODIFIED] ?? null,
            $attributes[self::ATTRIBUTE_MIME_TYPE] ?? null,
            $attributes[self::ATTRIBUTE_EXTRA_METADATA] ?? []
        );
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            self::ATTRIBUTE_TYPE           => self::TYPE_FILE,
            self::ATTRIBUTE_PATH           => $this->path,
            self::ATTRIBUTE_FILE_SIZE      => $this->fileSize,
            self::ATTRIBUTE_VISIBILITY     => $this->visibility,
            self::ATTRIBUTE_LAST_MODIFIED  => $this->lastModified,
            self::ATTRIBUTE_MIME_TYPE      => $this->mimeType,
            self::ATTRIBUTE_EXTRA_METADATA => $this->extraMetadata,
        ];
    }
}
