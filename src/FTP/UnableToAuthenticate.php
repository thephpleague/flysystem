<?php

declare(strict_types=1);

namespace League\Flysystem\FTP;

use RuntimeException;

final class UnableToAuthenticate extends RuntimeException implements FtpConnectionError
{
    public function __construct()
    {
        parent::__construct("Unable to login/authenticate with FTP");
    }
}
