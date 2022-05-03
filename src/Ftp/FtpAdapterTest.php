<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

use League\Flysystem\FilesystemAdapter;

use function mock_function;
use function reset_function_mocks;

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

    /**
     * @test
     */
    public function not_being_able_to_resolve_connection_root(): void
    {
        $options = FtpConnectionOptions::fromArray([
           'host' => 'localhost',
           'port' => 2121,
           'timestampsOnUnixListingsEnabled' => true,
           'root' => '/invalid/root',
           'username' => 'foo',
           'password' => 'pass',
        ]);

        $adapter = new FtpAdapter($options);

        $this->expectExceptionObject(UnableToResolveConnectionRoot::itDoesNotExist('/invalid/root'));

        $adapter->delete('something');
    }

    /**
     * @test
     */
    public function not_being_able_to_resolve_connection_root_pwd(): void
    {
        $options = FtpConnectionOptions::fromArray([
           'host' => 'localhost',
           'port' => 2121,
           'timestampsOnUnixListingsEnabled' => true,
           'root' => '/home/foo/upload/',
           'username' => 'foo',
           'password' => 'pass',
        ]);

        $this->expectExceptionObject(UnableToResolveConnectionRoot::couldNotGetCurrentDirectory());
        mock_function('ftp_pwd', false);

        $adapter = new FtpAdapter($options);
        $adapter->delete('something');
    }

    protected function tearDown(): void
    {
        reset_function_mocks();
    }
}
