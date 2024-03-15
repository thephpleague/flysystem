<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV3;

use League\Flysystem\FilesystemException;
use RuntimeException;

class UnableToAuthenticate extends RuntimeException implements FilesystemException
{
    private ?string $connectionError;

    public function __construct(string $message, ?string $lastError = null)
    {
        parent::__construct($message);
        $this->connectionError = $lastError;
    }

    public static function withPassword(?string $lastError = null): UnableToAuthenticate
    {
        return new UnableToAuthenticate('Unable to authenticate using a password.', $lastError);
    }

    public static function withPrivateKey(?string $lastError = null): UnableToAuthenticate
    {
        return new UnableToAuthenticate('Unable to authenticate using a private key.', $lastError);
    }

    public static function withSshAgent(?string $lastError = null): UnableToAuthenticate
    {
        return new UnableToAuthenticate('Unable to authenticate using an SSH agent.', $lastError);
    }

    public function connectionError(): ?string
    {
        return $this->connectionError;
    }
}
