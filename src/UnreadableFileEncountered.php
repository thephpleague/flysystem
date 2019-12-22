<?php

declare(strict_types=1);

namespace League\Flysystem;

use RuntimeException;

class UnreadableFileEncountered extends RuntimeException implements FilesystemError
{
    public static function atLocation(string $location)
    {
        return new static("Unreadable file encountered at location {$location}.");
    }
}
