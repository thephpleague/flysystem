<?php

declare(strict_types=1);

namespace League\Flysystem\PHPSecLibV2;

use League\Flysystem\FilesystemError;
use RuntimeException;

class UnableToEstablishAuthenticityOfHost extends RuntimeException implements FilesystemError
{
    public static function becauseTheAuthenticityCantBeEstablished(string $host): UnableToEstablishAuthenticityOfHost
    {
        return new UnableToEstablishAuthenticityOfHost("The authenticity of host $host can't be established.");
    }
}
