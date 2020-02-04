<?php

declare(strict_types=1);

namespace League\Flysystem\FTP;

use PHPUnit\Framework\TestCase;

class NoopCommandConnectivityCheckerTest extends TestCase
{
    /**
     * @test
     */
    public function detecting_a_good_connection()
    {
        $options = FTPConnectionOptions::fromArray([
           'host' => 'localhost',
           'port' => 2121,
           'root' => '/home/foo/upload',
           'username' => 'foo',
           'password' => 'pass',
       ]);
        $connection  = (new FTPConnectionProvider())->createConnection($options);
        $connected = (new NoopCommandConnectivityChecker())->isConnected($connection);

        $this->assertTrue($connected);
    }
    /**
     * @test
     */
    public function detecting_a_closed_connection()
    {
        $options = FTPConnectionOptions::fromArray([
           'host' => 'localhost',
           'port' => 2121,
           'root' => '/home/foo/upload',
           'username' => 'foo',
           'password' => 'pass',
       ]);
        $connection  = (new FTPConnectionProvider())->createConnection($options);
        ftp_close($connection);

        $connected = (new NoopCommandConnectivityChecker())->isConnected($connection);

        $this->assertFalse($connected);
    }
}
