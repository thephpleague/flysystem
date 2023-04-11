<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV2;

use phpseclib\Crypt\RSA;
use phpseclib\Net\SFTP;
use phpseclib\System\SSH\Agent;
use Throwable;

/**
 * @deprecated The "League\Flysystem\PhpseclibV2\SftpConnectionProvider" class is deprecated since Flysystem 3.0, use "League\Flysystem\PhpseclibV3\SftpConnectionProvider" instead.
 */
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
     * @var string|null
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
     * @var SFTP|null
     */
    private $connection;

    /**
     * @var ConnectivityChecker
     */
    private $connectivityChecker;

    /**
     * @var string|null
     */
    private $hostFingerprint;

    /**
     * @var string|null
     */
    private $privateKey;

    /**
     * @var string|null
     */
    private $passphrase;

    /**
     * @var int
     */
    private $maxTries;

    public function __construct(
        string $host,
        string $username,
        string $password = null,
        string $privateKey = null,
        string $passphrase = null,
        int $port = 22,
        bool $useAgent = false,
        int $timeout = 10,
        int $maxTries = 4,
        string $hostFingerprint = null,
        ConnectivityChecker $connectivityChecker = null,
        private bool $disableStatCache = true,
    ) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->privateKey = $privateKey;
        $this->passphrase = $passphrase;
        $this->useAgent = $useAgent;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->hostFingerprint = $hostFingerprint;
        $this->connectivityChecker = $connectivityChecker ?: new SimpleConnectivityChecker();
        $this->maxTries = $maxTries;
    }

    public function provideConnection(): SFTP
    {
        $tries = 0;
        start:

        $connection = $this->connection instanceof SFTP
            ? $this->connection
            : $this->setupConnection();

        if ( ! $this->connectivityChecker->isConnected($connection)) {
            $connection->disconnect();
            $this->connection = null;

            if ($tries < $this->maxTries) {
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
        $this->disableStatCache && $connection->disableStatCache();

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
            throw UnableToEstablishAuthenticityOfHost::becauseTheAuthenticityCantBeEstablished($this->host);
        }

        $fingerprint = $this->getFingerprintFromPublicKey($publicKey);

        if (0 !== strcasecmp($this->hostFingerprint, $fingerprint)) {
            throw UnableToEstablishAuthenticityOfHost::becauseTheAuthenticityCantBeEstablished($this->host);
        }
    }

    private function getFingerprintFromPublicKey(string $publicKey): string
    {
        $content = explode(' ', $publicKey, 3);

        return implode(':', str_split(md5(base64_decode($content[1])), 2));
    }

    private function authenticate(SFTP $connection): void
    {
        if ($this->privateKey !== null) {
            $this->authenticateWithPrivateKey($connection);
        } elseif ($this->useAgent) {
            $this->authenticateWithAgent($connection);
        } elseif ( ! $connection->login($this->username, $this->password)) {
            throw UnableToAuthenticate::withPassword();
        }
    }

    public static function fromArray(array $options): SftpConnectionProvider
    {
        return new SftpConnectionProvider(
            $options['host'],
            $options['username'],
            $options['password'] ?? null,
            $options['privateKey'] ?? null,
            $options['passphrase'] ?? null,
            $options['port'] ?? 22,
            $options['useAgent'] ?? false,
            $options['timeout'] ?? 10,
            $options['maxTries'] ?? 4,
            $options['hostFingerprint'] ?? null,
            $options['connectivityChecker'] ?? null
        );
    }

    private function authenticateWithPrivateKey(SFTP $connection): void
    {
        $privateKey = $this->loadPrivateKey();

        if ($connection->login($this->username, $privateKey)) {
            return;
        }

        if ($this->password !== null && $connection->login($this->username, $this->password)) {
            return;
        }

        throw UnableToAuthenticate::withPrivateKey();
    }

    private function loadPrivateKey(): RSA
    {
        if ("---" !== substr($this->privateKey, 0, 3) && is_file($this->privateKey)) {
            $this->privateKey = file_get_contents($this->privateKey);
        }

        $key = new RSA();

        if ($this->passphrase !== null) {
            $key->setPassword($this->passphrase);
        }

        if ( ! $key->loadKey($this->privateKey)) {
            throw new UnableToLoadPrivateKey();
        }

        return $key;
    }

    private function authenticateWithAgent(SFTP $connection): void
    {
        $agent = new Agent();

        if ( ! $connection->login($this->username, $agent)) {
            throw UnableToAuthenticate::withSshAgent();
        }
    }
}
