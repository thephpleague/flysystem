<?php

declare(strict_types=1);

namespace League\Flysystem;

use InvalidArgumentException;

use function gettype;
use function is_scalar;
use function var_export;

class InvalidVisibilityProvided extends InvalidArgumentException implements FlysystemException
{
    public static function withVisibility($visibility, string $expectedMessage)
    {
        $provided = is_scalar($visibility) ? var_export($visibility) : "parameter of type " . gettype($visibility);
        $message = "Invalid visibility provided. Expected {$expectedMessage}, received {$provided}";

        throw new static($message);
    }
}
