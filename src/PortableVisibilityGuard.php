<?php

declare(strict_types=1);

namespace League\Flysystem;

final class PortableVisibilityGuard
{
    public static function guardAgainstInvalidInput($visibility)
    {
        if ($visibility !== Visibility::PUBLIC && $visibility !== Visibility::PRIVATE) {
            $className = Visibility::class;
            throw InvalidVisibilityProvided::withVisibility(
                $visibility,
                "either {$className}::PUBLIC or {$className}::PRIVATE"
            );
        }
    }
}
