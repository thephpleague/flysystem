<?php

declare(strict_types=1);

namespace League\Flysystem\FTP;

use PHPUnit\Framework\TestCase;

class FTPConnectionProviderTest extends TestCase
{
    /**
     * @var FTPConnectionProvider
     */
    private $connectionProvider;

    /**
     * @before
     */
    public function setupConnectionProvider(): void
    {
        reset_function_mocks();
        $this->connectionProvider = new FTPConnectionProvider();
    }


    /**
     * @test
     */
    public function connecting_successfully()
    {
        $options = FTPConnectionOptions::fromArray([
            'host' => 'localhost',
            'port' => 2121,
            'uft8' => true,
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
        $options = FTPConnectionOptions::fromArray([
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
    public function not_being_able_to_connect()
    {
        $options = FTPConnectionOptions::fromArray([
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
        $options = FTPConnectionOptions::fromArray([
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
        $options = FTPConnectionOptions::fromArray([
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
