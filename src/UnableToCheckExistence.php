<?php

declare(strict_types=1);

namespace League\Flysystem;

use RuntimeException;
use Throwable;

class UnableToCheckExistence extends RuntimeException implements FilesystemOperationFailed
{
    public static function forLocation(string $path, Throwable $exception = null): static
    {
        return new static("Unable to check existence for: {$path}", 0, $exception);
    }

    public function operation(): string
    {
        return FilesystemOperationFailed::OPERATION_EXISTENCE_CHECK;
    }
}
