<?php

declare(strict_types=1);

namespace League\Flysystem\FTP;

use RuntimeException;

class UnableToMakeConnectionPassive extends RuntimeException implements FtpConnectionException
{
}
