<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

use FTP\Connection;

interface ConnectionProvider
{
    public function createConnection(FtpConnectionOptions $options): Connection;
}
