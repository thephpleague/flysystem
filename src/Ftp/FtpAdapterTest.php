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
        static::$connectionProvider = new StubConnectionProvider(new FtpConnectionProvider());

        return new FtpAdapter(
            $options,
            static::$connectionProvider,
            static::$connectivityChecker,
        );
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
        $adapter->__destruct();
        static::clearFilesystemAdapterCache();
        $this->assertFalse((new NoopCommandConnectivityChecker())->isConnected($connection));
    }

    /**
     * @test
     */
    public function it_can_disconnect(): void
    {
        /** @var FtpAdapter $adapter */
        $adapter = $this->adapter();

        $this->assertFalse($adapter->fileExists('not-existing.file'));

        self::assertTrue(static::$connectivityChecker->isConnected(static::$connectionProvider->connection));
        $adapter->disconnect();
        self::assertFalse(static::$connectivityChecker->isConnected(static::$connectionProvider->connection));
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

    /**
     * @test
     */
    public function normalizing_a_unix_timestamp_without_year_uses_the_current_year(): void
    {
        $now = new \DateTime('2024-06-15 12:00:00');

        $timestamp = $this->normalizeUnixTimestamp('Jun', '15', '10:00', $now);

        $this->assertSame((new \DateTime('2024-06-15 10:00:00'))->getTimestamp(), $timestamp);
    }

    /**
     * @test
     */
    public function normalizing_a_unix_timestamp_rolls_back_a_year_when_the_result_would_be_in_the_future(): void
    {
        // Reproduces the reported bug: a file modified 2024-09-30 12:58, listed by the FTP
        // server without a year (as `ls`-style listings do for entries within ~6 months),
        // viewed on 2025-02-19. Assuming the current year would report a future date.
        $now = new \DateTime('2025-02-19 09:00:00');

        $timestamp = $this->normalizeUnixTimestamp('Sep', '30', '12:58', $now);

        $this->assertSame((new \DateTime('2024-09-30 12:58:00'))->getTimestamp(), $timestamp);
    }

    /**
     * @test
     */
    public function normalizing_a_unix_timestamp_at_the_year_boundary(): void
    {
        // A file modified on December 31st of last year, listed without a year in early
        // January, must not be interpreted as a date in the future.
        $now = new \DateTime('2025-01-02 08:00:00');

        $timestamp = $this->normalizeUnixTimestamp('Dec', '31', '23:45', $now);

        $this->assertSame((new \DateTime('2024-12-31 23:45:00'))->getTimestamp(), $timestamp);
    }

    /**
     * @test
     */
    public function normalizing_a_unix_timestamp_with_an_explicit_year_is_unaffected(): void
    {
        $now = new \DateTime('2019-06-01 00:00:00');

        $timestamp = $this->normalizeUnixTimestamp('Jan', '1', '2019', $now);

        $this->assertSame((new \DateTime('2019-01-01 00:00:00'))->getTimestamp(), $timestamp);
    }

    private function normalizeUnixTimestamp(string $month, string $day, string $timeOrYear, \DateTimeInterface $now): int
    {
        $adapter = static::createFilesystemAdapter();
        $method = new \ReflectionMethod($adapter, 'normalizeUnixTimestamp');
        $method->setAccessible(true);

        return $method->invoke($adapter, $month, $day, $timeOrYear, $now);
    }

    protected function tearDown(): void
    {
        reset_function_mocks();
    }
}
