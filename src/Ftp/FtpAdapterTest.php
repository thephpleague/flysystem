<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

use League\Flysystem\FilesystemAdapter;

/**
 * @group ftp
 */
class FtpAdapterTest extends FtpAdapterTestCase
{
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

    /**
     * @test
     */
    public function disconnect_after_destruct(): void
    {
        /** @var FtpAdapter $adapter */
        $adapter = $this->adapter();
        $reflection = new \ReflectionObject($adapter);
        $adapter->fileExists('foo.txt');
        $reflectionProperty = $reflection->getProperty('connection');
        $reflectionProperty->setAccessible(true);
        $connection = $reflectionProperty->getValue($adapter);
        unset($reflection);

        $this->assertTrue(false !== ftp_pwd($connection));
        unset($adapter);
        static::clearFilesystemAdapterCache();
        $this->assertFalse((new NoopCommandConnectivityChecker())->isConnected($connection));
    }
}
