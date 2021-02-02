<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV3;

use phpseclib3\Net\SFTP;

class SimpleConnectivityChecker implements ConnectivityChecker
{
    public function isConnected(SFTP $connection): bool
    {
        return $connection->isConnected();
    }
}
