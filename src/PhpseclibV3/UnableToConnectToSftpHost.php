<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV3;

use League\Flysystem\FilesystemException;
use RuntimeException;

class UnableToConnectToSftpHost extends RuntimeException implements FilesystemException
{
    public static function atHostname(string $host): UnableToConnectToSftpHost
    {
        return new UnableToConnectToSftpHost("Unable to connect to host: $host");
    }
}
