<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV3;

use League\Flysystem\FilesystemException;
use phpseclib3\Crypt\Common\AsymmetricKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Exception\NoKeyLoadedException;
use phpseclib3\Net\SFTP;
use phpseclib3\System\SSH\Agent;
use Throwable;

use function base64_decode;
use function implode;
use function str_split;

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

    /**
     * @var array
     */
    private $preferredAlgorithms;

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
        array $preferredAlgorithms = []
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
        $this->preferredAlgorithms = $preferredAlgorithms;
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
        $connection->setPreferredAlgorithms($this->preferredAlgorithms);
        $connection->disableStatCache();

        try {
            $this->checkFingerprint($connection);
            $this->authenticate($connection);
        } catch (Throwable $exception) {
            $connection->disconnect();

            if ($exception instanceof FilesystemException) {
                throw $exception;
            }
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
        $algo = $content[0] === 'ssh-rsa' ? 'md5' : 'sha512';

        return implode(':', str_split(hash($algo, base64_decode($content[1])), 2));
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
            $options['connectivityChecker'] ?? null,
            $options['preferredAlgorithms'] ?? [],
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

    private function loadPrivateKey(): AsymmetricKey
    {
        if ("---" !== substr($this->privateKey, 0, 3) && is_file($this->privateKey)) {
            $this->privateKey = file_get_contents($this->privateKey);
        }

        try {
            if ($this->passphrase !== null) {
                return PublicKeyLoader::load($this->privateKey, $this->passphrase);
            }

            return PublicKeyLoader::load($this->privateKey);
        } catch (NoKeyLoadedException $exception) {
            throw new UnableToLoadPrivateKey();
        }
    }

    private function authenticateWithAgent(SFTP $connection): void
    {
        $agent = new Agent();

        if ( ! $connection->login($this->username, $agent)) {
            throw UnableToAuthenticate::withSshAgent();
        }
    }
}
