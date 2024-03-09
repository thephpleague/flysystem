<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

use League\Flysystem\AdapterTestUtilities\RetryOnTestException;
use PHPUnit\Framework\TestCase;

/**
 * @group ftp
 */
class NoopCommandConnectivityCheckerTest extends TestCase
{
    use RetryOnTestException;

    protected function setUp(): void
    {
        $this->retryOnException(UnableToConnectToFtpHost::class);
    }

    /**
     * @test
     */
    public function detecting_a_good_connection(): void
    {
        $options = FtpConnectionOptions::fromArray([
           'host' => 'localhost',
           'port' => 2121,
           'root' => '/home/foo/upload',
           'username' => 'foo',
           'password' => 'pass',
       ]);
        $connection = (new FtpConnectionProvider())->createConnection($options);
        $connected = (new NoopCommandConnectivityChecker())->isConnected($connection);

        $this->assertTrue($connected);
    }

    /**
     * @test
     */
    public function detecting_a_closed_connection(): void
    {
        $options = FtpConnectionOptions::fromArray([
            'host' => 'localhost',
            'port' => 2121,
            'root' => '/home/foo/upload',
            'username' => 'foo',
            'password' => 'pass',
        ]);

        $this->runScenario(function () use ($options) {
            $connection = (new FtpConnectionProvider())->createConnection($options);
            ftp_close($connection);

            $connected = (new NoopCommandConnectivityChecker())->isConnected($connection);

            $this->assertFalse($connected);
        });
    }
}
