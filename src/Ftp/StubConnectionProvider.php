<?php
declare(strict_types=1);

namespace League\Flysystem\Ftp;

class StubConnectionProvider implements ConnectionProvider
{
    public mixed $connection;

    public function __construct(private ConnectionProvider $provider)
    {
    }

    public function createConnection(FtpConnectionOptions $options)
    {
        return $this->connection = $this->provider->createConnection($options);
    }
}
