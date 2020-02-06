<?php

declare(strict_types=1);

namespace League\Flysystem\FTP;

use League\Flysystem\FilesystemError;
use RuntimeException;

class InvalidListResponseReceived extends RuntimeException implements FilesystemError
{
}
