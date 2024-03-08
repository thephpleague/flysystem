<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV3;

use phpseclib3\Net\SFTP;

/**
 * @method void disconnect()
 */
interface ConnectionProvider
{
    public function provideConnection(): SFTP;
}
