<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

interface ConnectionProvider
{
    /**
     * @return resource|\FTPConnection
     */
    public function createConnection(FtpConnectionOptions $options);
}
