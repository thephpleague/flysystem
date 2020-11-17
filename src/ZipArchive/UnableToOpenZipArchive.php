<?php

declare(strict_types=1);

namespace League\Flysystem\ZipArchive;

use RuntimeException;

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
}
