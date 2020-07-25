<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

use League\Flysystem\FilesystemException;
use RuntimeException;

class InvalidListResponseReceived extends RuntimeException implements FilesystemException
{
}
