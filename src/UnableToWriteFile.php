<?php

declare(strict_types=1);

namespace League\Flysystem;

use RuntimeException;

class UnableToWriteFile extends RuntimeException implements FilesystemOperationFailed
{
    private $location = '';

    public static function toLocation(string $location, string $message)
    {
        $e = new static("Unable to write to location: {$location}. {$message}");
        $e->location;

        return $e;
    }

    public function operationType(): string
    {
        return FilesystemOperationFailed::OPERATION_WRITE;
    }

    public function location(): string
    {
        return $this->location;
    }
}
