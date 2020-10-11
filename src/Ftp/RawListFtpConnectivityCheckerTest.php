<?php

namespace League\Flysystem\Ftp;

use PHPUnit\Framework\TestCase;

class RawListFtpConnectivityCheckerTest extends TestCase
{
    /**
     * @test
     */
    public function detecting_if_a_connection_is_connected(): void
    {
        $options = FtpConnectionOptions::fromArray([
           'host' => 'localhost',
           'port' => 2121,
           'root' => '/home/foo/upload/',
           'username' => 'foo',
           'password' => 'pass',
       ]);

        $provider = new FtpConnectionProvider();
        $connection = $provider->createConnection($options);

        $connectedChecker = new RawListFtpConnectivityChecker();

        $this->assertTrue($connectedChecker->isConnected($connection));

        @ftp_close($connection);

        $this->assertFalse($connectedChecker->isConnected($connection));
    }
}
