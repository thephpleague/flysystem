<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

interface ConnectivityChecker
{
    /**
     * @param resource $connection
     */
    public function isConnected($connection): bool;
}
