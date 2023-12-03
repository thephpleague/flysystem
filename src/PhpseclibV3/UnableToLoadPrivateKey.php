<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV3;

use League\Flysystem\FilesystemException;
use RuntimeException;
use Throwable;

class UnableToLoadPrivateKey extends RuntimeException implements FilesystemException
{
    public function __construct(?string $message = 'Unable to load private key.', ?Throwable $previous = null)
    {
        parent::__construct($message ?? 'Unable to load private key.', 0, $previous);
    }
}
