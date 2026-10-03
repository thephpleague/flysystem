<?php

declare(strict_types=1);

namespace League\Flysystem;

/**
 * Reads, writes and inspects the files and directories of a filesystem.
 */
interface FilesystemOperator extends FilesystemReader, FilesystemWriter
{
}
