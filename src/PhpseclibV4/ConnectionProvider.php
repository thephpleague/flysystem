<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV4;

use phpseclib4\Net\SFTP;

/**
 * @method void disconnect()
 */
interface ConnectionProvider
{
    public function provideConnection(): SFTP;
}
