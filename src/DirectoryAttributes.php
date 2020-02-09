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
            $attributes['path'],
            $attributes['visibility'] ?? null
        );
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'type' => $this->type,
            'path' => $this->path,
            'visibility' => $this->visibility,
        ];
    }
}
