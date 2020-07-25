<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV2;

use phpseclib\Net\SFTP;

interface ConnectionProvider
{
    public function provideConnection(): SFTP;
}
