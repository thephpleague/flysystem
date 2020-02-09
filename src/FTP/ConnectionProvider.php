<?php

declare(strict_types=1);

namespace League\Flysystem\FTP;

interface ConnectionProvider
{
    /**
     * @return resource
     */
    public function createConnection(FtpConnectionOptions $options);
}
