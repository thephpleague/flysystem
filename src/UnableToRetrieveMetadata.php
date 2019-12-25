<?php

declare(strict_types=1);

namespace League\Flysystem;

use RuntimeException;

class UnableToRetrieveMetadata extends RuntimeException implements FilesystemOperationFailed
{
    public const TYPE_VISIBILITY = 'VISIBILITY';

    /**
     * @var string
     */
    private $location;

    /**
     * @var string
     */
    private $metadataType;

    public function location(): string
    {
        return $this->location;
    }

    public function metadataType(): string
    {
        return $this->metadataType;
    }

    public static function visibility(string $location, string $extraMessage)
    {
        $e = new static("Unable to retrieve visibility for file at location: $location. {$extraMessage}");
        $e->location = $location;
        $e->metadataType = self::TYPE_VISIBILITY;

        return $e;
    }

    public function operationType(): string
    {
        return FilesystemOperationFailed::OPERATION_GET_VISIBILITY;
    }
}
