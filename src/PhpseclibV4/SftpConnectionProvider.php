<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV4;

use League\Flysystem\FilesystemException;
use phpseclib4\Crypt\Common\PrivateKey;
use phpseclib4\Crypt\PublicKeyLoader;
use phpseclib4\Exception\NoKeyLoadedException;
use phpseclib4\Net\SFTP;
use phpseclib4\System\SSH\Agent;
use Throwable;

use function base64_decode;
use function bin2hex;
use function hash;
use function hash_equals;
use function preg_match;
use function rtrim;
use function str_replace;
use function strtolower;

class SftpConnectionProvider implements ConnectionProvider
{

    /**
     * @var SFTP|null
     */
    private $connection;

    /**
     * @var ConnectivityChecker
     */
    private $connectivityChecker;

    public function __construct(
        private string $host,
        private string $username,
        private ?string $password = null,
        private ?string $privateKey = null,
        private ?string $passphrase = null,
        private int $port = 22,
        private bool $useAgent = false,
        private int $timeout = 10,
        private int $maxTries = 4,
        private string|array|null $hostFingerprint = null,
        ?ConnectivityChecker $connectivityChecker = null,
        private array $preferredAlgorithms = [],
        private bool $disableStatCache = true,
    ) {
        $this->connectivityChecker = $connectivityChecker ?? new SimpleConnectivityChecker();
    }

    public function provideConnection(): SFTP
    {
        $tries = 0;
        start:
        $tries++;

        try {
            $connection = $this->connection instanceof SFTP
                ? $this->connection
                : $this->setupConnection();
        } catch (Throwable $exception) {
            if ($tries <= $this->maxTries) {
                goto start;
            }

            if ($exception instanceof FilesystemException) {
                throw $exception;
            }

            throw UnableToConnectToSftpHost::atHostname($this->host, $exception);
        }

        if (! $this->connectivityChecker->isConnected($connection)) {
            $connection->disconnect();
            $this->connection = null;

            if ($tries <= $this->maxTries) {
                goto start;
            }

            throw UnableToConnectToSftpHost::atHostname($this->host);
        }

        return $this->connection = $connection;
    }

    public function disconnect(): void
    {
        if ($this->connection) {
            $this->connection->disconnect();
            $this->connection = null;
        }
    }

    private function setupConnection(): SFTP
    {
        $connection = new SFTP($this->host, $this->port, $this->timeout);
        $connection->setPreferredAlgorithms($this->preferredAlgorithms);
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
        if (! $this->hostFingerprint) {
            return;
        }

        try {
            $publicKey = $connection->getServerPublicHostKey();
        } catch (Throwable) {
            throw UnableToEstablishAuthenticityOfHost::becauseTheAuthenticityCantBeEstablished($this->host);
        }

        $expectedFingerprints = is_array($this->hostFingerprint)
            ? $this->hostFingerprint
            : [$this->hostFingerprint];

        foreach ($expectedFingerprints as $expectedFingerprint) {
            if ($this->fingerprintMatchesPublicKey($expectedFingerprint, $publicKey)) {
                return;
            }
        }

        throw UnableToEstablishAuthenticityOfHost::becauseTheAuthenticityCantBeEstablished($this->host);
    }

    private function fingerprintMatchesPublicKey(string $expectedFingerprint, string $publicKey): bool
    {
        $content = explode(' ', $publicKey, 3);
        $binaryKey = base64_decode($content[1]);

        $algorithms = ['md5', 'sha1', 'sha256', 'sha512'];

        if (preg_match('/^(md5|sha1|sha256|sha512):(.+)$/i', $expectedFingerprint, $matches)) {
            $algorithms = [strtolower($matches[1])];
            $expectedFingerprint = $matches[2];
        }

        foreach ($algorithms as $algorithm) {
            $binaryHash = hash($algorithm, $binaryKey, true);

            // Hex form: normalise by dropping the colon separators and lowercasing
            // so both "AB:CD" and "abcd" match a lowercase hex digest.
            $hexHash = bin2hex($binaryHash);
            $expectedHex = strtolower(str_replace(':', '', $expectedFingerprint));

            if (hash_equals($hexHash, $expectedHex)) {
                return true;
            }

            // Base64 form (OpenSSH SHA256 style), tolerating stripped padding.
            $base64Hash = rtrim(base64_encode($binaryHash), '=');
            $expectedBase64 = rtrim($expectedFingerprint, '=');

            if (hash_equals($base64Hash, $expectedBase64)) {
                return true;
            }
        }

        return false;
    }

    private function authenticate(SFTP $connection): void
    {
        if ($this->privateKey !== null) {
            $this->authenticateWithPrivateKey($connection);
        } elseif ($this->useAgent) {
            $this->authenticateWithAgent($connection);
        } else {
            $this->authenticateWithUsernameAndPassword($connection);
        }
    }

    private function authenticateWithUsernameAndPassword(SFTP $connection): void
    {
        if (! $connection->login($this->username, $this->password)) {
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
            $options['disableStatCache'] ?? true,
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

    private function loadPrivateKey(): PrivateKey
    {
        if (("---" !== substr($this->privateKey, 0, 3) || "PuTTY" !== substr($this->privateKey, 0, 5)) && is_file($this->privateKey)) {
            $this->privateKey = file_get_contents($this->privateKey);
        }

        try {
            if ($this->passphrase !== null) {
                return PublicKeyLoader::loadPrivateKey($this->privateKey, $this->passphrase);
            }

            return PublicKeyLoader::loadPrivateKey($this->privateKey);
        } catch (NoKeyLoadedException $exception) {
            throw new UnableToLoadPrivateKey(null, $exception);
        }
    }

    private function authenticateWithAgent(SFTP $connection): void
    {
        $agent = new Agent();

        if (! $connection->login($this->username, $agent)) {
            throw UnableToAuthenticate::withSshAgent();
        }
    }
}
