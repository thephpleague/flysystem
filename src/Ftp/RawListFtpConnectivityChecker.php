<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

class RawListFtpConnectivityChecker implements ConnectivityChecker
{
    /**
     * @inheritDoc
     */
    public function isConnected($connection): bool
    {
        return @ftp_rawlist($connection, './') !== false;
    }
}
