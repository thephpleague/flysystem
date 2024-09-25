<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

use FTP\Connection;

class ConnectivityCheckerThatCanFail implements ConnectivityChecker
{
    private bool $failNextCall = false;

    public function __construct(private ConnectivityChecker $connectivityChecker)
    {
    }

    public function failNextCall(): void
    {
        $this->failNextCall = true;
    }

    public function isConnected(Connection $connection): bool
    {
        if ($this->failNextCall) {
            $this->failNextCall = false;

            return false;
        }

        return $this->connectivityChecker->isConnected($connection);
    }
}
