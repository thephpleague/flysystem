<?php

declare(strict_types=1);

namespace League\Flysystem;

use InvalidArgumentException as BaseInvalidArgumentException;

class InvalidArgumentException extends BaseInvalidArgumentException implements FilesystemError
{
}
