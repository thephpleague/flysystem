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
        $response = is_resource($connection) ? @ftp_raw($connection, 'NOOP') : false;
        $responseCode = $response ? (int) preg_replace('/\D/', '', implode('', $response)) : false;

        return $responseCode === 200;
    }
}
