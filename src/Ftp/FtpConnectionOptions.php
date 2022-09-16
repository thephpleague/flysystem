<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

use const FTP_BINARY;

class FtpConnectionOptions
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $root;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var int
     */
    private $port;

    /**
     * @var bool
     */
    private $ssl;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var bool
     */
    private $utf8;

    /**
     * @var bool
     */
    private $passive;

    /**
     * @var int
     */
    private $transferMode;

    /**
     * @var string|null
     */
    private $systemType;

    /**
     * @var bool|null
     */
    private $ignorePassiveAddress;

    /**
     * @var bool
     */
    private $enableTimestampsOnUnixListings;

    /**
     * @var bool
     */
    private $recurseManually;

    private ?bool $useRawListOptions;

    public function __construct(
        string $host,
        string $root,
        string $username,
        string $password,
        int $port = 21,
        bool $ssl = false,
        int $timeout = 90,
        bool $utf8 = false,
        bool $passive = true,
        int $transferMode = FTP_BINARY,
        ?string $systemType = null,
        ?bool $ignorePassiveAddress = null,
        bool $enableTimestampsOnUnixListings = false,
        bool $recurseManually = false,
        ?bool $useRawListOptions = null,
    ) {
        $this->host = $host;
        $this->root = $root;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
        $this->ssl = $ssl;
        $this->timeout = $timeout;
        $this->utf8 = $utf8;
        $this->passive = $passive;
        $this->transferMode = $transferMode;
        $this->systemType = $systemType;
        $this->ignorePassiveAddress = $ignorePassiveAddress;
        $this->enableTimestampsOnUnixListings = $enableTimestampsOnUnixListings;
        $this->recurseManually = $recurseManually;
        $this->useRawListOptions = $useRawListOptions;
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
