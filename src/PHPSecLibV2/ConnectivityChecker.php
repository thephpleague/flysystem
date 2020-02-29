<?php

declare(strict_types=1);

namespace League\Flysystem\PHPSecLibV2;

use phpseclib\Net\SFTP;

interface ConnectivityChecker
{
    public function isConnected(SFTP $connection): bool;
}
