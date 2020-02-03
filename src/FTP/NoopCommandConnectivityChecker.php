<?php

declare(strict_types=1);

namespace League\Flysystem\FTP;

class NoopCommandConnectivityChecker implements ConnectivityChecker
{
    /**
     * @inheritDoc
     */
    public function isConnected($connection): bool
    {
        return @ftp_raw($connection, 'NOOP') !== false;
    }
}
