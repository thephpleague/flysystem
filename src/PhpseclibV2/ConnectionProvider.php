<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV2;

use phpseclib\Net\SFTP;

/**
 * @deprecated The "League\Flysystem\PhpseclibV2\ConnectionProvider" class is deprecated since Flysystem 3.0, use "League\Flysystem\PhpseclibV3\ConnectionProvider" instead.
 */
interface ConnectionProvider
{
    public function provideConnection(): SFTP;
}
