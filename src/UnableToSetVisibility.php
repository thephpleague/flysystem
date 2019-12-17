<?php

declare(strict_types=1);

namespace League\Flysystem;

use RuntimeException;

use function rtrim;

class UnableToSetVisibility extends RuntimeException implements FilesystemOperationFailed
{
    /**
     * @var string
     */
    private $location;

    public static function atLocation(string $filename, string $extraMessage = '')
    {
        $message = "'Unable to set visibility for file {$filename}. $extraMessage";
        $e = new static(rtrim($message));
        $e->location = $filename;

        return $e;
    }

    public function operationType(): string
    {
        return FilesystemOperationFailed::OPERATION_CREATE_DIRECTORY;
    }

    public function location(): string
    {
        return $this->location;
    }
}
