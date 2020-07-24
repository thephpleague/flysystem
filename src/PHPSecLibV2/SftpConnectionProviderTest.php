<?php

declare(strict_types=1);

namespace League\Flysystem\PHPSecLibV2;

use phpseclib\Net\SFTP;
use PHPUnit\Framework\TestCase;

/**
 * @group sftp
 * @group sftp-connection
 */
class SftpConnectionProviderTest extends TestCase
{
    /**
     * @test
     */
    public function giving_up_after_5_connection_failures(): void
    {
        $this->expectException(UnableToConnectToSftpHost::class);
        $provider = new SftpConnectionProvider(
            'localhost',
            'foo',
            'pass',
            null,
            null,
            2222,
            false,
            10,
            null,
            new FixatedConnectivityChecker(5)
        );
        $provider->provideConnection();
    }

    /**
     * @test
     */
    public function trying_until_5_tries(): void
    {
        $provider = new SftpConnectionProvider(
            'localhost',
            'foo',
            'pass',
            null,
            null,
            2222,
            false,
            10,
            null,
            new FixatedConnectivityChecker(4)
        );
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
                'privateKey' => __DIR__.'/../../test_files/sftp/id_rsa',
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
    public function authenticating_with_a_private_key_and_falling_back_to_password(): void
    {
        $provider = SftpConnectionProvider::fromArray(
            [
                'host' => 'localhost',
                'username' => 'foo',
                'password' => 'pass',
                'privateKey' => __DIR__.'/../../test_files/sftp/id_rsa',
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
    public function verifying_a_fingerprint(): void
    {
        $key = file_get_contents(__DIR__.'/../../test_files/sftp/ssh_host_rsa_key.pub');
        $fingerPrint = $this->computeFingerPrint($key);

        $provider = new SftpConnectionProvider('localhost', 'foo', 'pass', null, null, 2222, false, 10, $fingerPrint);
        $anotherConnection = $provider->provideConnection();
        $this->assertInstanceOf(SFTP::class, $anotherConnection);
    }

    /**
     * @test
     */
    public function providing_an_invalid_fingerprint(): void
    {
        $this->expectException(UnableToEstablishAuthenticityOfHost::class);
        $provider = new SftpConnectionProvider('localhost', 'foo', 'pass', null, null, 2222, false, 10, 'invalid:fingerprint');
        $provider->provideConnection();
    }

    /**
     * @test
     */
    public function providing_an_invalid_password(): void
    {
        $this->expectException(UnableToAuthenticate::class);
        $provider = new SftpConnectionProvider('localhost', 'foo', 'lol', null, null, 2222, false);
        $provider->provideConnection();
    }

    private function computeFingerPrint(string $publicKey): string
    {
        $content = explode(' ', $publicKey, 3);

        return implode(':', str_split(md5(base64_decode($content[1])), 2));
    }
}
