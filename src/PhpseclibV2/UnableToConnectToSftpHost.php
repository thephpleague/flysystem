<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV2;

use League\Flysystem\FilesystemException;
use RuntimeException;

/**
 * @deprecated The "League\Flysystem\PhpseclibV2\UnableToConnectToSftpHost" class is deprecated since Flysystem 3.0, use "League\Flysystem\PhpseclibV3\UnableToConnectToSftpHost" instead.
 */
class UnableToConnectToSftpHost extends RuntimeException implements FilesystemException
{
    public static function atHostname(string $host): UnableToConnectToSftpHost
    {
        return new UnableToConnectToSftpHost("Unable to connect to host: $host");
    }
}
