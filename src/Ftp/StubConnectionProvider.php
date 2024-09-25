<?php
declare(strict_types=1);

namespace League\Flysystem\Ftp;

use FTP\Connection;

class StubConnectionProvider implements ConnectionProvider
{
    public mixed $connection;

    public function __construct(private ConnectionProvider $provider)
    {
    }

    public function createConnection(FtpConnectionOptions $options): Connection
    {
        return $this->connection = $this->provider->createConnection($options);
    }
}
