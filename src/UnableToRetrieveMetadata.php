<?php

declare(strict_types=1);

namespace League\Flysystem;

use RuntimeException;
use Throwable;

class UnableToRetrieveMetadata extends RuntimeException implements FilesystemOperationFailed
{
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
        return static::create($location, $extraMessage, FileAttributes::ATTRIBUTE_LAST_MODIFIED, $previous);
    }

    public static function visibility(string $location, string $extraMessage, Throwable $previous = null): self
    {
        return static::create($location, $extraMessage, FileAttributes::ATTRIBUTE_VISIBILITY, $previous);
    }

    public static function fileSize(string $location, string $extraMessage, Throwable $previous = null): self
    {
        return static::create($location, $extraMessage, FileAttributes::ATTRIBUTE_FILE_SIZE, $previous);
    }

    public static function mimeType(string $location, string $extraMessage, Throwable $previous = null): self
    {
        return static::create($location, $extraMessage, FileAttributes::ATTRIBUTE_MIME_TYPE, $previous);
    }

    public static function create(string $location, string $extraMessage, string $type, Throwable $previous = null): self
    {
        $e = new static("Unable to retrieve the $type for file at location: $location. {$extraMessage}", 0, $previous);
        $e->location = $location;
        $e->metadataType = $type;

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
