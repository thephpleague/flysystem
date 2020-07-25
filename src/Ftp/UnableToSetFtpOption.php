<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

use RuntimeException;

class UnableToSetFtpOption extends RuntimeException implements FtpConnectionException
{
    public static function whileSettingOption(string $option): UnableToSetFtpOption
    {
        return new UnableToSetFtpOption("Unable to set FTP option $option.");
    }
}
