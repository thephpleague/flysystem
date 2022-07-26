<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV2;

use phpseclib\Net\SFTP;
use PHPUnit\Framework\TestCase;

use function class_exists;

/**
 * @group sftp
 * @group sftp-connection
 * @group legacy
 */
class SftpConnectionProviderTest extends TestCase
{
    protected function setUp(): void
    {
        if ( ! class_exists('phpseclib\Net\SFTP')) {
            self::markTestSkipped("PHPSecLib V2 is not installed");
        }

        parent::setUp();
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
                'connectivityChecker' => new FixatedConnectivityChecker(5)
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
            'connectivityChecker' => new FixatedConnectivityChecker(4)
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
        $provider = SftpConnectionProvider::fromArray(
            [
                'host' => 'localhost',
                'username' => 'bar',
                'privateKey' => __DIR__ . '/../../test_files/sftp/id_rsa',
                'passphrase' => 'secret',
                'port' => 2222,
            ]
        );

        $connection = $provider->provideConnection();
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

        $provider->provideConnection();
    }

    /**
     * @test
     */
    public function authenticating_with_an_ssh_agent(): void
    {
        $provider = SftpConnectionProvider::fromArray(
            [
                'host' => 'localhost',
                'username' => 'bar',
                'useAgent' => true,
                'port' => 2222,
            ]
        );

        $connection = $provider->provideConnection();
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

        $connection = $provider->provideConnection();
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
        $provider->provideConnection();
    }

    /**
     * @test
     */
    public function verifying_a_fingerprint(): void
    {
        $key = file_get_contents(__DIR__ . '/../../test_files/sftp/ssh_host_rsa_key.pub');
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

        $anotherConnection = $provider->provideConnection();
        $this->assertInstanceOf(SFTP::class, $anotherConnection);
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
        $provider->provideConnection();
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
        $provider->provideConnection();
    }

    private function computeFingerPrint(string $publicKey): string
    {
        $content = explode(' ', $publicKey, 3);

        return implode(':', str_split(md5(base64_decode($content[1])), 2));
    }
}
