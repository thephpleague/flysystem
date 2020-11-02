<?php

declare(strict_types=1);

namespace League\Flysystem\ZipArchive;

use RuntimeException;
use Throwable;

final class UnableToOpenZipArchive extends RuntimeException implements ZipArchiveException
{
    public static function atLocation(string $location, string $reason = ''): self
    {
        return new self(rtrim(sprintf(
            'Unable to open file at location: %s. %s',
            $location,
            $reason
        )));
    }

    public static function failedToCreateParentDirectory(string $location, Throwable $previous): self
    {
        return new self(
            sprintf(
                'Unable to create file at location: %s. Failed to create parent directory: %s.',
                $location,
                dirname($location)
            ),
            0,
            $previous
        );
    }
}
