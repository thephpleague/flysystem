<?php

declare(strict_types=1);

namespace League\Flysystem;

use InvalidArgumentException;

final class Visibility
{
    public const PUBLIC = 'public';
    public const PRIVATE = 'private';

    public static function exists(string $input): bool
    {
        return $input === self::PUBLIC || $input === self::PRIVATE;
    }

    public static function guardAgainstInvalidVisibility(string $input)
    {
        if (static::exists($input) === false) {
            throw new InvalidArgumentException("Incorrect visibility: " . $input);
        }
    }
}
