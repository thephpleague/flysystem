<?php

declare(strict_types=1);

namespace League\Flysystem;

class DirectoryAttributes implements StorageAttributes
{
    use ProxyArrayAccessToProperties;

    /**
     * @var string
     */
    private $type = StorageAttributes::TYPE_DIRECTORY;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string|null
     */
    private $visibility;

    /**
     * @var int|null
     */
    private $lastModified;

    public function __construct(string $path, ?string $visibility = null, ?int $lastModified = null)
    {
        $this->path = $path;
        $this->visibility = $visibility;
        $this->lastModified = $lastModified;
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

    public function lastModified(): ?int
    {
        return $this->lastModified;
    }

    public function isFile(): bool
    {
        return false;
    }

    public function isDir(): bool
    {
        return true;
    }

    public static function fromArray(array $attributes): StorageAttributes
    {
        return new DirectoryAttributes(
            $attributes[StorageAttributes::ATTRIBUTE_PATH],
            $attributes[StorageAttributes::ATTRIBUTE_VISIBILITY] ?? null,
            $attributes[StorageAttributes::ATTRIBUTE_LAST_MODIFIED] ?? null
        );
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            StorageAttributes::ATTRIBUTE_TYPE => $this->type,
            StorageAttributes::ATTRIBUTE_PATH => $this->path,
            StorageAttributes::ATTRIBUTE_VISIBILITY => $this->visibility,
            StorageAttributes::ATTRIBUTE_LAST_MODIFIED => $this->lastModified,
        ];
    }
}
