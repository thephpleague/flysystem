<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

use ValueError;

class RawListFtpConnectivityChecker implements ConnectivityChecker
{
    /**
     * @inheritDoc
     */
    public function isConnected($connection): bool
    {
        try {
            return $connection !== false && @ftp_rawlist($connection, './') !== false;
        } catch (ValueError $errror) {
            return false;
        }
    }
}
