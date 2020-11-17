<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

use League\Flysystem\FilesystemAdapter;

/**
 * @group ftp
 */
class FtpAdapterTest extends FtpAdapterTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->retryOnException(UnableToConnectToFtpHost::class);
    }

    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        $options = FtpConnectionOptions::fromArray([
           'host' => 'localhost',
           'port' => 2121,
           'timestampsOnUnixListingsEnabled' => true,
           'root' => '/home/foo/upload/',
           'username' => 'foo',
           'password' => 'pass',
       ]);

        static::$connectivityChecker = new ConnectivityCheckerThatCanFail(new NoopCommandConnectivityChecker());

        return new FtpAdapter($options, null, static::$connectivityChecker);
    }
}
