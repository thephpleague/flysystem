<?php

declare(strict_types=1);

namespace League\Flysystem\FTP;

use PHPUnit\Framework\TestCase;

/**
 * @group ftp
 */
class FtpConnectionProviderTest extends TestCase
{
    /**
     * @var FtpConnectionProvider
     */
    private $connectionProvider;

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
    public function connecting_successfully()
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

        $connection  = $this->connectionProvider->createConnection($options);

        $this->assertIsResource($connection);
        $this->assertTrue(ftp_close($connection));
    }

    /**
     * @test
     */
    public function not_being_able_to_enable_uft8_mode()
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

        $this->connectionProvider->createConnection($options);
    }

    /**
     * @test
     */
    public function not_being_able_to_ignore_the_passive_address()
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

        $this->connectionProvider->createConnection($options);
    }

    /**
     * @test
     */
    public function not_being_able_to_make_the_connection_passive()
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

        $this->connectionProvider->createConnection($options);
    }

    /**
     * @test
     */
    public function not_being_able_to_connect()
    {
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
    public function not_being_able_to_connect_over_ssl()
    {
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
    public function not_being_able_to_authenticate()
    {
        $options = FtpConnectionOptions::fromArray([
           'host' => 'localhost',
           'port' => 2121,
           'root' => '/home/foo/upload',
           'username' => 'foo',
           'password' => 'lolnope',
       ]);

        $this->expectException(UnableToAuthenticate::class);

        $this->connectionProvider->createConnection($options);
    }
}
