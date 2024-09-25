<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

use FTP\Connection;
use TypeError;
use ValueError;

class NoopCommandConnectivityChecker implements ConnectivityChecker
{
    public function isConnected(Connection $connection): bool
    {
        // @codeCoverageIgnoreStart
        try {
            $response = @ftp_raw($connection, 'NOOP');
        } catch (TypeError | ValueError) {
            return false;
        }
        // @codeCoverageIgnoreEnd

        $responseCode = $response ? (int) preg_replace('/\D/', '', implode('', $response)) : false;

        return $responseCode === 200;
    }
}
