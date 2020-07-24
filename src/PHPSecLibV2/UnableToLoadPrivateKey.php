<?php

declare(strict_types=1);

namespace League\Flysystem\PHPSecLibV2;

use League\Flysystem\FilesystemException;
use RuntimeException;

class UnableToLoadPrivateKey extends RuntimeException implements FilesystemException
{
    public function __construct($message = "Unable to load private key.")
    {
        parent::__construct($message);
    }
}
