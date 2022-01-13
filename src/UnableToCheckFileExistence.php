<?php

declare(strict_types=1);

namespace League\Flysystem;

use RuntimeException;
use Throwable;

class UnableToCheckFileExistence extends UnableToCheckExistence
{
    public static function forLocation(string $path, Throwable $exception = null): static
    {
        return new static("Unable to check existence for: ${path}", 0, $exception);
    }

    public function operation(): string
    {
        return FilesystemOperationFailed::OPERATION_FILE_EXISTS;
    }
}
