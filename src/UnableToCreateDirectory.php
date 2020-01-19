<?php

declare(strict_types=1);

namespace League\Flysystem;

use RuntimeException;

class UnableToCreateDirectory extends RuntimeException implements FilesystemOperationFailed
{
    /**
     * @var string
     */
    private $location;

    public static function atLocation(string $dirname, string $errorMessage = '')
    {
        $message = "'Unable to create a directory at {$dirname}. ${errorMessage}";
        $e = new static(rtrim($message));
        $e->location = $dirname;

        return $e;
    }

    public function operation(): string
    {
        return FilesystemOperationFailed::OPERATION_CREATE_DIRECTORY;
    }

    public function location(): string
    {
        return $this->location;
    }
}
