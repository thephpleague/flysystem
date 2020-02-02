<?php

declare(strict_types=1);

namespace League\Flysystem\FTP;

use RuntimeException;

class UnableToSetFtpOption extends RuntimeException implements FtpConnectionError
{
    public static function whileSettingOption(string $option)
    {
        return new static("Unable to set FTP option $option.");
    }
}
