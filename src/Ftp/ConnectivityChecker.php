<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

use FTP\Connection;

interface ConnectivityChecker
{
    public function isConnected(Connection $connection): bool;
}
