<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV3;

use League\Flysystem\FilesystemException;
use RuntimeException;
use Throwable;

class UnableToConnectToSftpHost extends RuntimeException implements FilesystemException
{
    public static function atHostname(string $host, ?Throwable $previous = null): UnableToConnectToSftpHost
    {
        return new UnableToConnectToSftpHost("Unable to connect to host: $host", 0, $previous);
    }
}
