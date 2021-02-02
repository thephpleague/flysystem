<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV3;

use League\Flysystem\FilesystemException;
use RuntimeException;

class UnableToLoadPrivateKey extends RuntimeException implements FilesystemException
{
    public function __construct(string $message = "Unable to load private key.")
    {
        parent::__construct($message);
    }
}
