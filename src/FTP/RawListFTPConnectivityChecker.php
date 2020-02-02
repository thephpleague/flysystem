<?php

declare(strict_types=1);

namespace League\Flysystem\FTP;

class RawListFTPConnectivityChecker implements ConnectivityChecker
{
    /**
     * @inheritDoc
     */
    public function isConnected($connection): bool
    {
        return @ftp_rawlist($connection, './') !== false;
    }
}
