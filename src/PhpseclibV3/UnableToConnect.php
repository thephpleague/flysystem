<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV3;

class UnableToConnect extends UnableToAuthenticate
{
    protected const ERROR_PREFIX = 'SSH_MSG_DISCONNECT: ';

    public static function disconnected(string $error): UnableToConnect
    {
        $cleanError = str_after(trim($error), self::ERROR_PREFIX);
        $reason = str_after($cleanError, "\r\n");
        $code = array_search(str_before($cleanError, "\r\n"), static::reasons());

        return new UnableToConnect(
            self::exceptionMessagePrefix() . $reason,
            $code,
        );
    }

    public static function matches($error): bool
    {
        return str_starts_with(trim($error), self::ERROR_PREFIX);
    }

    public function getReasonCode(): string
    {
        return static::reasons()[$this->getCode()];
    }

    public function getDisconnectReason(): string
    {
        return str_after(
            $this->getMessage(),
            self::exceptionMessagePrefix()
        );
    }

    protected static function exceptionMessagePrefix(): string
    {
        return 'Unable to authenticate using a password. Disconnected by application: ';
    }

    protected static function reasons(): array
    {
        return [
            1 => 'NET_SSH2_DISCONNECT_HOST_NOT_ALLOWED_TO_CONNECT',
            2 => 'NET_SSH2_DISCONNECT_PROTOCOL_ERROR',
            3 => 'NET_SSH2_DISCONNECT_KEY_EXCHANGE_FAILED',
            4 => 'NET_SSH2_DISCONNECT_RESERVED',
            5 => 'NET_SSH2_DISCONNECT_MAC_ERROR',
            6 => 'NET_SSH2_DISCONNECT_COMPRESSION_ERROR',
            7 => 'NET_SSH2_DISCONNECT_SERVICE_NOT_AVAILABLE',
            8 => 'NET_SSH2_DISCONNECT_PROTOCOL_VERSION_NOT_SUPPORTED',
            9 => 'NET_SSH2_DISCONNECT_HOST_KEY_NOT_VERIFIABLE',
            10 => 'NET_SSH2_DISCONNECT_CONNECTION_LOST',
            11 => 'NET_SSH2_DISCONNECT_BY_APPLICATION',
            12 => 'NET_SSH2_DISCONNECT_TOO_MANY_CONNECTIONS',
            13 => 'NET_SSH2_DISCONNECT_AUTH_CANCELLED_BY_USER',
            14 => 'NET_SSH2_DISCONNECT_NO_MORE_AUTH_METHODS_AVAILABLE',
            15 => 'NET_SSH2_DISCONNECT_ILLEGAL_USER_NAME'
        ];
    }
}
