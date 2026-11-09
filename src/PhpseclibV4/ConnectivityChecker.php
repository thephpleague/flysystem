<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV4;

use phpseclib4\Net\SFTP;

interface ConnectivityChecker
{
    public function isConnected(SFTP $connection): bool;
}
