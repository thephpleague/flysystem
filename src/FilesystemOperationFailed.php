<?php

declare(strict_types=1);

namespace League\Flysystem;

interface FilesystemOperationFailed extends FilesystemError
{
    public const OPERATION_WRITE = 'WRITE';
    public const OPERATION_UPDATE = 'UPDATE';
    public const OPERATION_CREATE_DIRECTORY = 'CREATE_DIRECTORY';
    public const OPERATION_DELETE = 'DELETE';
    public const OPERATION_DELETE_DIRECTORY = 'DELETE_DIRECTORY';
    public const OPERATION_MOVE = 'MOVE';
    public const OPERATION_GET_VISIBILITY = 'GET_VISIBILITY';
    public const OPERATION_COPY = 'COPY';

    public function operationType(): string;
}
