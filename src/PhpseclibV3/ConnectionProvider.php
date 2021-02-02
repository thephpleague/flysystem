<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV3;

use phpseclib3\Net\SFTP;

interface ConnectionProvider
{
    public function provideConnection(): SFTP;
}
