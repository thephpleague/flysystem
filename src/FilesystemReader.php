<?php

declare(strict_types=1);

namespace League\Flysystem;

/**
 * This interface contains everything to read from and inspect
 * a filesystem. All methods containing are non-destructive.
 */
interface FilesystemReader
{
    public function fileExists(string $location): bool;

    /**
     * @throws UnableToReadFile
     */
    public function read(string $location): string;

    /**
     * @throws UnableToReadFile
     */
    public function readStream(string $location);

    public function listContents(string $location, bool $recursive = false): DirectoryListing;

    /**
     * @throws UnableToRetrieveMetadata
     */
    public function lastModified(string $path): int;

    /**
     * @throws UnableToRetrieveMetadata
     */
    public function fileSize(string $path): int;

    /**
     * @throws UnableToRetrieveMetadata
     */
    public function mimeType(string $path): string;

    /**
     * @throws UnableToRetrieveMetadata
     */
    public function visibility(string $path): string;
}
