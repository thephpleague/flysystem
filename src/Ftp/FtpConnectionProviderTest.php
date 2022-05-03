<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

use League\Flysystem\AdapterTestUtilities\RetryOnTestException;
use PHPUnit\Framework\TestCase;

use function ftp_close;

/**
 * @group ftp
 */
class FtpConnectionProviderTest extends TestCase
{
    use RetryOnTestException;

    /**
     * @var FtpConnectionProvider
     */
    private $connectionProvider;

    protected function setUp(): void
    {
        $this->retryOnException(UnableToConnectToFtpHost::class);
    }

    /**
     * @before
     */
    public function setupConnectionProvider(): void
    {
        $this->connectionProvider = new FtpConnectionProvider();
    }

    /**
     * @after
     */
    public function resetFunctionMocks(): void
    {
        reset_function_mocks();
    }

    /**
     * @test
     */
    public function connecting_successfully(): void
    {
        $options = FtpConnectionOptions::fromArray([
            'host' => 'localhost',
            'port' => 2121,
            'utf8' => true,
            'passive' => true,
            'ignorePassiveAddress' => true,
            'root' => '/home/foo/upload',
            'username' => 'foo',
            'password' => 'pass',
        ]);

        $this->runScenario(function () use ($options) {
            $connection = $this->connectionProvider->createConnection($options);
            $this->assertTrue(ftp_close($connection));
        });
    }

    /**
     * @test
     */
    public function not_being_able_to_enable_uft8_mode(): void
    {
        $options = FtpConnectionOptions::fromArray([
            'host' => 'localhost',
            'port' => 2121,
            'utf8' => true,
            'root' => '/home/foo/upload',
            'username' => 'foo',
            'password' => 'pass',
       ]);

        mock_function('ftp_raw', ['Error']);

        $this->expectException(UnableToEnableUtf8Mode::class);

        $this->runScenario(function () use ($options) {
            $this->connectionProvider->createConnection($options);
        });
    }

    /**
     * @test
     */
    public function uft8_mode_already_active_by_server(): void
    {
        $options = FtpConnectionOptions::fromArray([
            'host' => 'localhost',
            'port' => 2121,
            'utf8' => true,
            'root' => '/home/foo/upload',
            'username' => 'foo',
            'password' => 'pass',
       ]);

        mock_function('ftp_raw', ['202 UTF8 mode is always enabled. No need to send this command.']);
        $this->expectNotToPerformAssertions();

        $this->runScenario(function () use ($options) {
            $this->connectionProvider->createConnection($options);
        });
    }

    /**
     * @test
     */
    public function not_being_able_to_ignore_the_passive_address(): void
    {
        $options = FtpConnectionOptions::fromArray([
            'host' => 'localhost',
            'port' => 2121,
            'ignorePassiveAddress' => true,
            'root' => '/home/foo/upload',
            'username' => 'foo',
            'password' => 'pass',
       ]);

        mock_function('ftp_set_option', false);

        $this->expectException(UnableToSetFtpOption::class);

        $this->runScenario(function () use ($options) {
            $this->connectionProvider->createConnection($options);
        });
    }

    /**
     * @test
     */
    public function not_being_able_to_make_the_connection_passive(): void
    {
        $options = FtpConnectionOptions::fromArray([
            'host' => 'localhost',
            'port' => 2121,
            'utf8' => true,
            'root' => '/home/foo/upload',
            'username' => 'foo',
            'password' => 'pass',
       ]);

        mock_function('ftp_pasv', false);

        $this->expectException(UnableToMakeConnectionPassive::class);

        $this->runScenario(function () use ($options) {
            $this->connectionProvider->createConnection($options);
        });
    }

    /**
     * @test
     */
    public function not_being_able_to_connect(): void
    {
        $this->dontRetryOnException();

        $options = FtpConnectionOptions::fromArray([
           'host' => 'localhost',
           'port' => 313131,
           'root' => '/home/foo/upload',
           'username' => 'foo',
           'password' => 'pass',
        ]);

        $this->expectException(UnableToConnectToFtpHost::class);

        $this->connectionProvider->createConnection($options);
    }

    /**
     * @test
     */
    public function not_being_able_to_connect_over_ssl(): void
    {
        $this->dontRetryOnException();

        $options = FtpConnectionOptions::fromArray([
           'host' => 'localhost',
           'ssl' => true,
           'port' => 313131,
           'root' => '/home/foo/upload',
           'username' => 'foo',
           'password' => 'pass',
        ]);

        $this->expectException(UnableToConnectToFtpHost::class);

        $this->connectionProvider->createConnection($options);
    }

    /**
     * @test
     */
    public function not_being_able_to_authenticate(): void
    {
        $options = FtpConnectionOptions::fromArray([
           'host' => 'localhost',
           'port' => 2121,
           'root' => '/home/foo/upload',
           'username' => 'foo',
           'password' => 'lolnope',
       ]);

        $this->expectException(UnableToAuthenticate::class);
        $this->retryOnException(UnableToConnectToFtpHost::class);
        $this->runScenario(function () use ($options) {
            $this->connectionProvider->createConnection($options);
        });
    }
}
