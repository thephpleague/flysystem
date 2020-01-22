<?php

declare(strict_types=1);

namespace League\Flysystem\PHPSecLibV2;

use phpseclib\Net\SFTP;
use Throwable;

use const STDOUT;

class SftpConnectionProvider implements ConnectionProvider
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var bool
     */
    private $useAgent;

    /**
     * @var int
     */
    private $port;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var SFTP
     */
    private $connection;

    /**
     * @var ConnectivityChecker
     */
    private $connectivityChecker;

    /**
     * @var string
     */
    private $hostFingerprint;

    public function __construct(
        string $host,
        string $username,
        string $password = null,
        int $port = 22,
        bool $useAgent = false,
        int $timeout = 10,
        string $hostFingerprint = null,
        ConnectivityChecker $connectivityChecker = null
    ) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->useAgent = $useAgent;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->hostFingerprint = $hostFingerprint;
        $this->connectivityChecker = $connectivityChecker ?: new SimpleConnectivityChecker();
    }

    public function provideConnection(): SFTP
    {
        $tries = 0;
        start:
        $connection = $this->connection instanceof SFTP
            ? $this->connection
            : $this->setupConnection();

        while( ! $this->connectivityChecker->isConnected($connection)) {
            $connection->disconnect();
            $this->connection = null;

            if ($tries < 5) {
                $tries++;
                goto start;
            }

            throw UnableToConnectToSftpHost::atHostname($this->host);
        }

        return $this->connection = $connection;
    }

    private function setupConnection(): SFTP
    {
        $connection = new SFTP($this->host, $this->port, $this->timeout);

        try {
            $this->checkFingerprint($connection);
            $this->authenticate($connection);
        } catch (Throwable $exception) {
            $connection->disconnect();
            throw $exception;
        }

        return $connection;
    }

    private function checkFingerprint(SFTP $connection): void
    {
        if ( ! $this->hostFingerprint) {
            return;
        }

        $publicKey = $connection->getServerPublicHostKey();

        if ($publicKey === false) {
            throw UnableToEstablishAuthenticityOfHost::becauseTheServerHasNoPublicHostKey($this->host);
        }

        $fingerprint = $this->getFingerprintFromPublicKey($publicKey);

        if (0 !== strcasecmp($this->hostFingerprint, $fingerprint)) {
            throw UnableToEstablishAuthenticityOfHost::becauseTheAuthenticityCantBeEstablished($this->host);
        }
    }

    private function getFingerprintFromPublicKey(string $publickey): string
    {
        $content = explode(' ', $publickey, 3);

        return implode(':', str_split(md5(base64_decode($content[1])), 2));
    }

    private function authenticate(SFTP $connection): void
    {
        if ( ! $connection->login($this->username, $this->password)) {
            throw new UnableToAuthenticate('Can\'t authenticate.');
        }
    }
}
