<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV3;

use phpseclib3\Net\SFTP;
use Throwable;

class SimpleConnectivityChecker implements ConnectivityChecker
{
    public function __construct(
        private bool $usePing = false,
    ) {
    }

    public static function create(): SimpleConnectivityChecker
    {
        return new SimpleConnectivityChecker();
    }

    public function withUsingPing(bool $usePing): SimpleConnectivityChecker
    {
        $clone = clone $this;
        $clone->usePing = $usePing;

        return $clone;
    }

    public function isConnected(SFTP $connection): bool
    {
        if ( ! $connection->isConnected()) {
            return false;
        }

        if ( ! $this->usePing) {
            return true;
        }

        try {
            return $connection->ping();
        } catch (Throwable) {
            return false;
        }
    }
}
