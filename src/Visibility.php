<?php

declare(strict_types=1);

namespace League\Flysystem;

use InvalidArgumentException;

final class Visibility
{
    public const PUBLIC = 'public';
    public const PRIVATE = 'private';
    public const UNKNOWN = 'unknown';

    public static function exists(string $visibility): bool
    {
        return $visibility === self::PUBLIC
            || $visibility === self::PRIVATE
            || $visibility === self::UNKNOWN;
    }

    public static function guardAgainstInvalidVisibility(string $input)
    {
        if (static::exists($input) === false) {
            throw new InvalidArgumentException("Incorrect visibility: " . $input);
        }
    }
}
