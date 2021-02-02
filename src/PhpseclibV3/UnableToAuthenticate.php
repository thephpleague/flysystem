<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV3;

use League\Flysystem\FilesystemException;
use RuntimeException;

class UnableToAuthenticate extends RuntimeException implements FilesystemException
{
    public static function withPassword(): UnableToAuthenticate
    {
        return new UnableToAuthenticate('Unable to authenticate using a password.');
    }

    public static function withPrivateKey(): UnableToAuthenticate
    {
        return new UnableToAuthenticate('Unable to authenticate using a private key.');
    }

    public static function withSshAgent(): UnableToAuthenticate
    {
        return new UnableToAuthenticate('Unable to authenticate using an SSH agent.');
    }
}
