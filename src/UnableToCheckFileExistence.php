<?php

declare(strict_types=1);

namespace League\Flysystem;

use RuntimeException;
use Throwable;

class UnableToCheckFileExistence extends RuntimeException implements FilesystemOperationFailed
{
    public static function forLocation(string $path, Throwable $exception = null): UnableToCheckFileExistence
    {
        return new UnableToCheckFileExistence("Unable to check file existence for: ${path}", 0, $exception);
    }

    public function operation(): string
    {
        return FilesystemOperationFailed::OPERATION_FILE_EXISTS;
    }
}
