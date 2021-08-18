<?php

declare(strict_types=1);

namespace League\Flysystem;

use RuntimeException;
use Throwable;

final class UnableToCopyFile extends RuntimeException implements FilesystemOperationFailed
{
    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $destination;

    public function source(): string
    {
        return $this->source;
    }

    public function destination(): string
    {
        return $this->destination;
    }

    public static function fromLocationTo(
        string $sourcePath,
        string $destinationPath,
        Throwable $previous = null
    ): UnableToCopyFile {
        $e = new static("Unable to copy file from $sourcePath to $destinationPath", 0 , $previous);
        $e->source = $sourcePath;
        $e->destination = $destinationPath;

        return $e;
    }

    public function operation(): string
    {
        return FilesystemOperationFailed::OPERATION_COPY;
    }
}
