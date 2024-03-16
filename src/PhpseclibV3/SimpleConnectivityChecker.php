<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV3;

use phpseclib3\Net\SFTP;
use Throwable;

class SimpleConnectivityChecker implements ConnectivityChecker
{
    public function isConnected(SFTP $connection): bool
    {
        if ( ! $connection->isConnected()) {
            return false;
        }

        try {
            return $connection->ping();
        } catch (Throwable) {
            return false;
        }
    }
}
