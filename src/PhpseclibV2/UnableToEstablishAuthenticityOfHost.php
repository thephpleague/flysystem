<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV2;

use League\Flysystem\FilesystemException;
use RuntimeException;

/**
 * @deprecated The "League\Flysystem\PhpseclibV2\UnableToEstablishAuthenticityOfHost" class is deprecated since Flysystem 3.0, use "League\Flysystem\PhpseclibV3\UnableToEstablishAuthenticityOfHost" instead.
 */
class UnableToEstablishAuthenticityOfHost extends RuntimeException implements FilesystemException
{
    public static function becauseTheAuthenticityCantBeEstablished(string $host): UnableToEstablishAuthenticityOfHost
    {
        return new UnableToEstablishAuthenticityOfHost("The authenticity of host $host can't be established.");
    }
}
