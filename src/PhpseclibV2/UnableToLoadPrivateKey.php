<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV2;

use League\Flysystem\FilesystemException;
use RuntimeException;

/**
 * @deprecated The "League\Flysystem\PhpseclibV2\UnableToLoadPrivateKey" class is deprecated since Flysystem 3.0, use "League\Flysystem\PhpseclibV3\UnableToLoadPrivateKey" instead.
 */
class UnableToLoadPrivateKey extends RuntimeException implements FilesystemException
{
    public function __construct(string $message = "Unable to load private key.")
    {
        parent::__construct($message);
    }
}
