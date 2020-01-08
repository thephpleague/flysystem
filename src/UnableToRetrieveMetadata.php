<?php

declare(strict_types=1);

namespace League\Flysystem;

use RuntimeException;
use Throwable;

class UnableToRetrieveMetadata extends RuntimeException implements FilesystemOperationFailed
{
    public const TYPE_VISIBILITY = 'VISIBILITY';
    public const TYPE_LAST_MODIFIED = 'LAST_MODIFIED';

    /**
     * @var string
     */
    private $location;

    /**
     * @var string
     */
    private $metadataType;

    public static function lastModified(string $location, string $extraMessage, Throwable $previous = null): self
    {
        $e = new static("Unable to retrieve visibility for file at location: $location. {$extraMessage}", 0, $previous);
        $e->location = $location;
        $e->metadataType = self::TYPE_LAST_MODIFIED;

        return $e;
    }

    public static function visibility(string $location, string $extraMessage, Throwable $previous = null): self
    {
        $e = new static("Unable to retrieve visibility for file at location: $location. {$extraMessage}", 0, $previous);
        $e->location = $location;
        $e->metadataType = self::TYPE_VISIBILITY;

        return $e;
    }

    public static function fileSize(string $location, string $extraMessage, Throwable $previous = null): self
    {
        $e = new static("Unable to retrieve the size for file at location: $location. {$extraMessage}", 0, $previous);
        $e->location = $location;
        $e->metadataType = self::TYPE_VISIBILITY;

        return $e;
    }

    public static function mimeType(string $location, string $extraMessage, Throwable $previous = null): self
    {
        $e = new static("Unable to retrieve the mimetype for file at location: $location. {$extraMessage}", 0, $previous);
        $e->location = $location;
        $e->metadataType = self::TYPE_VISIBILITY;

        return $e;
    }

    public function location(): string
    {
        return $this->location;
    }

    public function metadataType(): string
    {
        return $this->metadataType;
    }

    public function operation(): string
    {
        return FilesystemOperationFailed::OPERATION_RETRIEVE_METADATA;
    }
}
