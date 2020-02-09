<?php

declare(strict_types=1);

namespace League\Flysystem\PHPSecLibV2;

use phpseclib\Net\SFTP;
use PHPUnit\Framework\TestCase;

class SftpConnectionProviderTest extends TestCase
{
    /**
     * @test
     */
    public function giving_up_after_5_connection_failures(): void
    {
        $this->expectException(UnableToConnectToSftpHost::class);
        $provider = new SftpConnectionProvider('localhost', 'foo', 'pass', 2222, false, 10, null, new FixatedConnectivityChecker(5));
        $provider->provideConnection();
    }

    /**
     * @test
     */
    public function trying_until_5_tries(): void
    {
        $provider = new SftpConnectionProvider('localhost', 'foo', 'pass', 2222, false, 10, null, new FixatedConnectivityChecker(4));
        $connection = $provider->provideConnection();
        $sameConnection = $provider->provideConnection();

        $this->assertInstanceOf(SFTP::class, $connection);
        $this->assertSame($connection, $sameConnection);
    }

    /**
     * @test
     */
    public function verifying_a_fingerprint(): void
    {
        $provider = new SftpConnectionProvider('localhost', 'foo', 'pass', 2222);
        $connection = $provider->provideConnection();

        $key = $connection->getServerPublicHostKey();
        $fingerPrint = $this->computeFingerPrint($key);

        $provider = new SftpConnectionProvider('localhost', 'foo', 'pass', 2222, false, 10, $fingerPrint);
        $anotherConnection = $provider->provideConnection();
        $this->assertInstanceOf(SFTP::class, $anotherConnection);
    }

    /**
     * @test
     */
    public function providing_an_invalid_fingerprint(): void
    {
        $this->expectException(UnableToEstablishAuthenticityOfHost::class);
        $provider = new SftpConnectionProvider('localhost', 'foo', 'pass', 2222, false, 10, 'invalid:fingerprint');
        $provider->provideConnection();
    }

    /**
     * @test
     */
    public function providing_an_invalid_password(): void
    {
        $this->expectException(UnableToAuthenticate::class);
        $provider = new SftpConnectionProvider('localhost', 'foo', 'lol', 2222, false);
        $provider->provideConnection();
    }

    private function computeFingerPrint(string $publicKey): string
    {
        $content = explode(' ', $publicKey, 3);

        return implode(':', str_split(md5(base64_decode($content[1])), 2));
    }
}
