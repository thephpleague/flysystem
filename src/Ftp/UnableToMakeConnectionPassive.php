<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

use RuntimeException;

class UnableToMakeConnectionPassive extends RuntimeException implements FtpConnectionException
{
}
