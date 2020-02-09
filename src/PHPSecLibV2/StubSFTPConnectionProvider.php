<?php

declare(strict_types=1);

namespace League\Flysystem\PHPSecLibV2;

use phpseclib\Net\SFTP;

class StubSFTPConnectionProvider implements ConnectionProvider
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string|null
     */
    private $password;

    /**
     * @var int
     */
    private $port;

    /**
     * @var SftpStub
     */
    private $connection;

    public function __construct(
        string $host,
        string $username,
        string $password = null,
        int $port = 22
    ) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
    }

    public function provideConnection(): SFTP
    {
        if ( ! $this->connection instanceof SFTP) {
            $connection = new SftpStub($this->host, $this->port);
            $connection->login($this->username, $this->password);

            $this->connection = $connection;
        }

        return $this->connection;
    }
}
