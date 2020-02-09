<?php

declare(strict_types=1);

namespace League\Flysystem\FTP;

use const FTP_USEPASVADDRESS;

class FtpConnectionProvider implements ConnectionProvider
{
    /**
     * @return resource
     * @throws FtpConnectionError
     */
    public function createConnection(FtpConnectionOptions $options)
    {
        $connection = $this->createConnectionResource($options->host(), $options->port(), $options->timeout(), $options->ssl());

        try {
            $this->authenticate($options, $connection);
            $this->enableUtf8Mode($options, $connection);
            $this->ignorePassiveAddress($options, $connection);
            $this->makeConnectionPassive($options, $connection);
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

    private function authenticate(FtpConnectionOptions $options, $connection): void
    {
        if ( ! @ftp_login($connection, $options->username(), $options->password())) {
            throw new UnableToAuthenticate();
        }
    }

    private function enableUtf8Mode(FtpConnectionOptions $options, $connection): void
    {
        if ( ! $options->utf8()) {
            return;
        }

        $response = ftp_raw($connection, "OPTS UTF8 ON");

        if (substr($response[0] ?? '', 0, 3) !== '200') {
            throw new UnableToEnableUtf8Mode('Could not set UTF-8 mode for connection: ' . $options->host() . '::' . $options->port());
        }
    }

    private function ignorePassiveAddress(FtpConnectionOptions $options, $connection)
    {
        $ignorePassiveAddress = $options->ignorePassiveAddress();

        if ( ! is_bool($ignorePassiveAddress) || ! defined('FTP_USEPASVADDRESS')) {
            return;
        }

        if ( ! ftp_set_option($connection, FTP_USEPASVADDRESS, ! $ignorePassiveAddress)) {
            throw UnableToSetFtpOption::whileSettingOption('FTP_USEPASVADDRESS');
        }
    }

    private function makeConnectionPassive(FtpConnectionOptions $options, $connection)
    {
        if ( ! ftp_pasv($connection, $options->passive())) {
            throw new UnableToMakeConnectionPassive(
                'Could not set passive mode for connection: ' . $options->host() . '::' . $options->port()
            );
        }
    }
}
