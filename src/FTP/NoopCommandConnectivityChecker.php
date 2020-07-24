<?php

declare(strict_types=1);

namespace League\Flysystem\FTP;

use TypeError;

class NoopCommandConnectivityChecker implements ConnectivityChecker
{
    public function isConnected($connection): bool
    {
        try {
            $response = @ftp_raw($connection, 'NOOP');
        } catch (TypeError $typeError) {
            return false;
        }

        $responseCode = $response ? (int) preg_replace('/\D/', '', implode('', $response)) : false;

        return $responseCode === 200;
    }
}
