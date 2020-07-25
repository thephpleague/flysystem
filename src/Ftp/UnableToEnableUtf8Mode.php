<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

use RuntimeException;

final class UnableToEnableUtf8Mode extends RuntimeException implements FtpConnectionException
{
}
