<?php

declare(strict_types=1);

namespace League\Flysystem;

use RuntimeException;

class UnableToDeleteFile extends RuntimeException implements FilesystemOperationFailed
{
    private $location = '';

    /**
     * @var string
     */
    private $reason;

    public static function atLocation(string $location, string $reason = '')
    {
        $e = new static(rtrim("Unable to delete file located at: {$location}. {$reason}"));
        $e->location;
        $e->reason = $reason;

        return $e;
    }

    public function operationType(): string
    {
        return FilesystemOperationFailed::OPERATION_DELETE;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function location(): string
    {
        return $this->location;
    }
}
