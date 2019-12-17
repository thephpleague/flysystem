<?php

declare(strict_types=1);

namespace League\Flysystem;

interface FilesystemOperationFailed extends FlysystemException
{
    public const OPERATION_WRITE = 'WRITE';
    public const OPERATION_CREATE_DIRECTORY = 'CREATE_DIRECTORY';

    public function operationType(): string;
}
