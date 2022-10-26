<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

use const FTP_BINARY;

class FtpConnectionOptions
{
    public function __construct(
        private string $host,
        private string $root,
        private string $username,
        private string $password,
        private int $port = 21,
        private bool $ssl = false,
        private int $timeout = 90,
        private bool $utf8 = false,
        private bool $passive = true,
        private int $transferMode = FTP_BINARY,
        private ?string $systemType = null,
        private ?bool $ignorePassiveAddress = null,
        private bool $enableTimestampsOnUnixListings = false,
        private bool $recurseManually = false,
        private ?bool $useRawListOptions = null,
    ) {
    }

    public function host(): string
    {
        return $this->host;
    }

    public function root(): string
    {
        return $this->root;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function port(): int
    {
        return $this->port;
    }

    public function ssl(): bool
    {
        return $this->ssl;
    }

    public function timeout(): int
    {
        return $this->timeout;
    }

    public function utf8(): bool
    {
        return $this->utf8;
    }

    public function passive(): bool
    {
        return $this->passive;
    }

    public function transferMode(): int
    {
        return $this->transferMode;
    }

    public function systemType(): ?string
    {
        return $this->systemType;
    }

    public function ignorePassiveAddress(): ?bool
    {
        return $this->ignorePassiveAddress;
    }

    public function timestampsOnUnixListingsEnabled(): bool
    {
        return $this->enableTimestampsOnUnixListings;
    }

    public function recurseManually(): bool
    {
        return $this->recurseManually;
    }

    public function useRawListOptions(): ?bool
    {
        return $this->useRawListOptions;
    }

    public static function fromArray(array $options): FtpConnectionOptions
    {
        return new FtpConnectionOptions(
            $options['host'] ?? 'invalid://host-not-set',
            $options['root'] ?? '',
            $options['username'] ?? 'invalid://username-not-set',
            $options['password'] ?? 'invalid://password-not-set',
            $options['port'] ?? 21,
            $options['ssl'] ?? false,
            $options['timeout'] ?? 90,
            $options['utf8'] ?? false,
            $options['passive'] ?? true,
            $options['transferMode'] ?? FTP_BINARY,
            $options['systemType'] ?? null,
            $options['ignorePassiveAddress'] ?? null,
            $options['timestampsOnUnixListingsEnabled'] ?? false,
            $options['recurseManually'] ?? true,
            $options['useRawListOptions'] ?? null,
        );
    }
}
