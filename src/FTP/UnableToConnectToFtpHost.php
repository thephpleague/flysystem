<?php

declare(strict_types=1);

namespace League\Flysystem\FTP;

use RuntimeException;

final class UnableToConnectToFtpHost extends RuntimeException implements FtpConnectionError
{
    public static function forHost(string $host, int $port, bool $ssl)
    {
        $usingSsl = $ssl ? ', using ssl' : '';

        return new static("Unable to connect to host $host at port $port$usingSsl.");
    }
}
