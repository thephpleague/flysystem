<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV3;

use League\Flysystem\AdapterTestUtilities\ToxiproxyManagement;
use phpseclib3\Net\SFTP;
use PHPUnit\Framework\TestCase;
use Throwable;

use function base64_decode;
use function class_exists;
use function explode;
use function getenv;
use function hash;
use function implode;
use function is_a;
use function sleep;
use function str_split;

/**
 * @group sftp
 * @group sftp-connection
 * @group phpseclib3
 */
class SftpConnectionProviderTest extends TestCase
{
    const KEX_ACCEPTED_BY_DEFAULT_OPENSSH_BUT_DISABLED_IN_EDDSA_ONLY = 'diffie-hellman-group14-sha256';

    public static function setUpBeforeClass(): void
    {
        if ( ! class_exists(SFTP::class)) {
            self::markTestIncomplete("No phpseclib v3 installed");
        }
    }

    /**
     * @test
     */
    public function giving_up_after_5_connection_failures(): void
    {
        $this->expectException(UnableToConnectToSftpHost::class);

        $provider = SftpConnectionProvider::fromArray(
            [
                'host' => 'localhost',
                'username' => 'foo',
                'password' => 'pass',
                'port' => 2222,
                'timeout' => 10,
                'connectivityChecker' => new FixatedConnectivityChecker(5),
            ]
        );

        $provider->provideConnection();
    }

    /**
     * @test
     */
    public function trying_until_5_tries(): void
    {
        $provider = SftpConnectionProvider::fromArray([
            'host' => 'localhost',
            'username' => 'foo',
            'password' => 'pass',
            'port' => 2222,
            'timeout' => 10,
            'connectivityChecker' => new FixatedConnectivityChecker(4),
        ]);
        $connection = $provider->provideConnection();
        $sameConnection = $provider->provideConnection();

        $this->assertInstanceOf(SFTP::class, $connection);
        $this->assertSame($connection, $sameConnection);
    }

    /**
     * @test
     */
    public function authenticating_with_a_private_key(): void
    {
        $provider = SftpConnectionProvider::fromArray([
            'host' => 'localhost',
            'username' => 'bar',
            'privateKey' => __DIR__ . '/../../test_files/sftp/id_rsa',
            'passphrase' => 'secret',
            'port' => 2222,
        ]);

        $connection = null;
        $this->runWithRetries(function () use (&$connection, $provider) {
            $connection = $provider->provideConnection();
        });
        $this->assertInstanceOf(SFTP::class, $connection);
    }

    /**
     * @test
     */
    public function authenticating_with_an_invalid_private_key(): void
    {
        $provider = SftpConnectionProvider::fromArray(
            [
                'host' => 'localhost',
                'username' => 'bar',
                'privateKey' => __DIR__ . '/../../test_files/sftp/users.conf',
                'port' => 2222,
            ]
        );

        $this->expectException(UnableToLoadPrivateKey::class);

        $this->runWithRetries(fn () => $provider->provideConnection(), UnableToLoadPrivateKey::class);
    }

    /**
     * @test
     */
    public function authenticating_with_an_ssh_agent(): void
    {
        if (getenv('COMPOSER_OPTS') === false) {
            $this->markTestSkipped('Test is not run locally');
        }

        $provider = SftpConnectionProvider::fromArray(
            [
                'host' => 'localhost',
                'username' => 'bar',
                'useAgent' => true,
                'port' => 2222,
            ]
        );

        $connection = null;
        $this->runWithRetries(function () use ($provider, &$connection) {
            $connection = $provider->provideConnection();
        });
        $this->assertInstanceOf(SFTP::class, $connection);
    }

    /**
     * @test
     */
    public function failing_to_authenticating_with_an_ssh_agent(): void
    {
        $this->expectException(UnableToAuthenticate::class);

        $provider = SftpConnectionProvider::fromArray(
            [
                'host' => 'localhost',
                'username' => 'foo',
                'useAgent' => true,
                'port' => 2222,
            ]
        );

        $provider->provideConnection();
    }

    /**
     * @test
     */
    public function authenticating_with_a_private_key_and_falling_back_to_password(): void
    {
        $provider = SftpConnectionProvider::fromArray(
            [
                'host' => 'localhost',
                'username' => 'foo',
                'password' => 'pass',
                'privateKey' => __DIR__ . '/../../test_files/sftp/id_rsa',
                'passphrase' => 'secret',
                'port' => 2222,
            ]
        );

        $connection = null;
        $this->runWithRetries(function () use ($provider, &$connection) {
            $connection = $provider->provideConnection();
        });
        $this->assertInstanceOf(SFTP::class, $connection);
    }

    /**
     * @test
     */
    public function not_being_able_to_authenticate_with_a_private_key(): void
    {
        $provider = SftpConnectionProvider::fromArray(
            [
                'host' => 'localhost',
                'username' => 'foo',
                'privateKey' => __DIR__ . '/../../test_files/sftp/unknown.key',
                'passphrase' => 'secret',
                'port' => 2222,
            ]
        );

        $this->expectExceptionObject(UnableToAuthenticate::withPrivateKey());
        $this->runWithRetries(fn () => $provider->provideConnection(), UnableToAuthenticate::class);
    }

    /**
     * @test
     */
    public function verifying_a_fingerprint(): void
    {
        $key = file_get_contents(__DIR__ . '/../../test_files/sftp/ssh_host_ed25519_key.pub');
        $fingerPrint = $this->computeFingerPrint($key);

        $provider = SftpConnectionProvider::fromArray(
            [
                'host' => 'localhost',
                'username' => 'foo',
                'password' => 'pass',
                'port' => 2222,
                'hostFingerprint' => $fingerPrint,
            ]
        );

        $connection = null;
        $this->runWithRetries(function () use ($provider, &$connection) {
            $connection = $provider->provideConnection();
        });
        $this->assertInstanceOf(SFTP::class, $connection);
    }

    /**
     * @test
     */
    public function providing_an_invalid_fingerprint(): void
    {
        $this->expectException(UnableToEstablishAuthenticityOfHost::class);

        $provider = SftpConnectionProvider::fromArray(
            [
                'host' => 'localhost',
                'username' => 'foo',
                'password' => 'pass',
                'port' => 2222,
                'hostFingerprint' => 'invalid:fingerprint',
            ]
        );
        $this->runWithRetries(fn () => $provider->provideConnection(), UnableToEstablishAuthenticityOfHost::class);
    }

    /**
     * @test
     */
    public function providing_an_invalid_password(): void
    {
        $this->expectException(UnableToAuthenticate::class);

        $provider = SftpConnectionProvider::fromArray(
            [
                'host' => 'localhost',
                'username' => 'foo',
                'password' => 'lol',
                'port' => 2222,
            ]
        );

        $this->runWithRetries(fn () => $provider->provideConnection(), UnableToAuthenticate::class);
    }

    /**
     * @test
     */
    public function retries_several_times_until_failure(): void
    {
        $connectivityChecker = new class implements ConnectivityChecker {
            /** @var int */
            public $calls = 0;

            public function isConnected(SFTP $connection): bool
            {
                ++$this->calls;

                return $connection->isConnected();
            }
        };

        $managesConnectionToxics = ToxiproxyManagement::forServer();
        $managesConnectionToxics->resetPeerOnRequest('sftp', 10);

        $maxTries = 2;

        $provider = SftpConnectionProvider::fromArray(
            [
                'host' => 'localhost',
                'username' => 'bar',
                'privateKey' => __DIR__ . '/../../test_files/sftp/id_rsa',
                'passphrase' => 'secret',
                'port' => 8222,
                'maxTries' => $maxTries,
                'timeout' => 1,
                'connectivityChecker' => $connectivityChecker,
            ]
        );

        $this->expectException(UnableToConnectToSftpHost::class);

        try {
            $provider->provideConnection();
        } finally {
            $managesConnectionToxics->removeAllToxics();

            self::assertSame($maxTries + 1, $connectivityChecker->calls);
        }
    }

    /**
     * @test
     */
    public function authenticate_with_supported_preferred_kex_algorithm_succeeds(): void
    {
        $provider = SftpConnectionProvider::fromArray(
            [
                'host' => 'localhost',
                'username' => 'foo',
                'password' => 'pass',
                'port' => 2222,
                'preferredAlgorithms' => [
                    'kex' => [self::KEX_ACCEPTED_BY_DEFAULT_OPENSSH_BUT_DISABLED_IN_EDDSA_ONLY],
                ],
            ]
        );

        $this->runWithRetries(fn () => $this->assertInstanceOf(SFTP::class, $provider->provideConnection()));

        $provider = SftpConnectionProvider::fromArray(
            [
                'host' => 'localhost',
                'username' => 'foo',
                'password' => 'pass',
                'port' => 2223,
                'preferredAlgorithms' => [
                    'kex' => ['curve25519-sha256'],
                ],
            ]
        );

        $this->runWithRetries(fn () => $this->assertInstanceOf(SFTP::class, $provider->provideConnection()));
    }

    /**
     * @test
     */
    public function authenticate_with_unsupported_preferred_kex_algorithm_failes(): void
    {
        $provider = SftpConnectionProvider::fromArray(
            [
                'host' => 'localhost',
                'username' => 'foo',
                'password' => 'pass',
                'port' => 2223,
                'preferredAlgorithms' => [
                    'kex' => [self::KEX_ACCEPTED_BY_DEFAULT_OPENSSH_BUT_DISABLED_IN_EDDSA_ONLY],
                ],
            ]
        );

        $this->expectException(UnableToConnectToSftpHost::class);

        $provider->provideConnection();
    }

    private function computeFingerPrint(string $publicKey): string
    {
        $content = explode(' ', $publicKey, 3);
        $algo = $content[0] === 'ssh-rsa' ? 'md5' : 'sha512';

        return implode(':', str_split(hash($algo, base64_decode($content[1])), 2));
    }

    /**
     * @param class-string<Throwable>|null $expected
     *
     * @throws Throwable
     */
    public function runWithRetries(callable $scenario, string $expected = null): void
    {
        $tries = 0;
        start:

        try {
            $scenario();
        } catch (Throwable $exception) {
            if (($expected === null || is_a($exception, $expected) === false) && $tries < 10) {
                $tries++;
                sleep($tries);
                goto start;
            }

            throw $exception;
        }
    }
}
