<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

use FTP\Connection;
use ValueError;

class RawListFtpConnectivityChecker implements ConnectivityChecker
{
    /**
     * @inheritDoc
     */
    public function isConnected(Connection $connection): bool
    {
        try {
            return $connection !== false && @ftp_rawlist($connection, './') !== false;
        } catch (ValueError) {
            return false;
        }
    }
}
