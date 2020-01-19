<?php

declare(strict_types=1);

namespace League\Flysystem\PHPSecLibV2;

use League\Flysystem\FilesystemError;
use RuntimeException;

class UnableToAuthenticate extends RuntimeException implements FilesystemError
{
}
