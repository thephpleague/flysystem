<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV3;

use phpseclib3\Net\SFTP;

class StubSftpConnectionProvider implements ConnectionProvider
{
    /**
     * @var SftpStub|null
     */
    public $connection;

    public function __construct(
        private string $host,
        private string $username,
        private ?string $password = null,
        private int $port = 22
    ) {
    }

    public function disconnect(): void
    {
        if ($this->connection) {
            $this->connection->disconnect();
        }
    }

    public function provideConnection(): SFTP
    {
        if ( ! $this->connection instanceof SFTP || ! $this->connection->isConnected()) {
            $connection = new SftpStub($this->host, $this->port);
            $connection->login($this->username, $this->password);

            $this->connection = $connection;
        }

        return $this->connection;
    }
}
