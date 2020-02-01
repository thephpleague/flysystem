<?php

declare(strict_types=1);

namespace League\Flysystem\FTP;

class FTPConnectionProvider implements ConnectionProvider
{
    /**
     * @return resource
     * @throws FtpConnectionError
     */
    public function createConnection(FTPConnectionOptions $options)
    {
        $connection = $this->createConnectionResource($options->host(), $options->port(), $options->timeout(), $options->ssl());

        try {
            $this->authenticate($options, $connection);
            $this->enableUtf8Mode($options, $connection);
        } catch (FtpConnectionError $exception) {
            ftp_close($connection);
            throw $exception;
        }

        return $connection;
    }

    private function createConnectionResource(string $host, int $port, int $timeout, bool $ssl)
    {
        $connection = $ssl
            ? @ftp_ssl_connect($host, $port, $timeout)
            : @ftp_connect($host, $port, $timeout);

        if ($connection === false) {
            throw UnableToConnectToFtpHost::forHost($host, $port, $ssl);
        }

        return $connection;
    }

    private function authenticate(FTPConnectionOptions $options, $connection): void
    {
        if ( ! @ftp_login($connection, $options->username(), $options->password())) {
            throw new UnableToAuthenticate();
        }
    }

    private function enableUtf8Mode(FTPConnectionOptions $options, $connection): void
    {
        if ( ! $options->utf8()) {
            return;
        }

        $response = ftp_raw($connection, "OPTS UTF8 ON");

        if (substr($response[0] ?? '', 0, 3) !== '200') {
            throw new UnableToEnableUtf8Mode('Could not set UTF-8 mode for connection: ' . $options->host() . '::' . $options->port());
        }
    }
}
