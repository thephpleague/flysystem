<?php

declare(strict_types=1);

namespace League\Flysystem;

use Throwable;

interface FilesystemOperationFailed extends Throwable
{
    public const OPERATION_WRITE = 'WRITE';
    public const OPERATION_CREATE_DIRECTORY = 'CREATE_DIRECTORY';

    public function operationType(): string;
}
