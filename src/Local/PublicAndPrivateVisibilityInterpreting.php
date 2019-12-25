<?php

declare(strict_types=1);

namespace League\Flysystem\Local;

use League\Flysystem\InvalidVisibilityProvided;
use League\Flysystem\Visibility;

class PublicAndPrivateVisibilityInterpreting implements LocalVisibilityInterpreting
{
    /**
     * @var int
     */
    private $filePublic;

    /**
     * @var int
     */
    private $filePrivate;

    /**
     * @var int
     */
    private $directoryPublic;

    /**
     * @var int
     */
    private $directoryPrivate;

    /**
     * @var string
     */
    private $defaultForDirectories;

    public function __construct(
        int $filePublic = 0644,
        int $filePrivate = 0600,
        int $directoryPublic = 0755,
        int $directoryPrivate = 0700,
        string $defaultForDirectories = Visibility::PRIVATE
    ) {
        $this->filePublic = $filePublic;
        $this->filePrivate = $filePrivate;
        $this->directoryPublic = $directoryPublic;
        $this->directoryPrivate = $directoryPrivate;
        $this->defaultForDirectories = $defaultForDirectories;
    }

    public function forFile($visibility): int
    {
        $this->guardAgainstInvalidInput($visibility);

        return $visibility === Visibility::PUBLIC
            ? $this->filePublic
            : $this->filePrivate;
    }

    public function forDirectory($visibility): int
    {
        $this->guardAgainstInvalidInput($visibility);

        return $visibility === Visibility::PUBLIC
            ? $this->directoryPublic
            : $this->directoryPrivate;
    }

    public function inverseForFile($visibility): string
    {
        if ($visibility === $this->filePublic) {
            return Visibility::PUBLIC;
        } elseif ($visibility === $this->filePrivate) {
            return Visibility::PRIVATE;
        }

        return Visibility::PUBLIC; // default
    }

    public function inverseForDirectory($visibility): string
    {
        $this->guardAgainstInvalidInput($visibility);
    }

    public function defaultForDirectories(): int
    {
        return $this->defaultForDirectories === Visibility::PUBLIC ? $this->directoryPublic : $this->directoryPrivate;
    }

    private function guardAgainstInvalidInput($visibility)
    {
        if ($visibility !== Visibility::PUBLIC && $visibility !== Visibility::PRIVATE) {
            $className = Visibility::class;
            throw InvalidVisibilityProvided::withVisibility(
                $visibility,
                "either {$className}::PUBLIC or {$className}::PRIVATE"
            );
        }
    }

    public static function fromArray(array $permissionMap, string $defaultForDirectories = Visibility::PRIVATE): PublicAndPrivateVisibilityInterpreting
    {
        return new static(
            $permissionMap['file']['public'] ?? 0644,
            $permissionMap['file']['private'] ?? 0600,
            $permissionMap['dir']['public'] ?? 0755,
            $permissionMap['dir']['private'] ?? 0755,
            $defaultForDirectories
        );
    }
}
