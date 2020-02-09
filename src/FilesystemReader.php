<?php

declare(strict_types=1);

namespace League\Flysystem;

/**
 * This interface contains everything to read from and inspect
 * a filesystem. All methods containing are non-destructive.
 */
interface FilesystemReader
{
    /**
     * @throws FilesystemError
     */
    public function fileExists(string $location): bool;

    /**
     * @throws UnableToReadFile
     * @throws FilesystemError
     */
    public function read(string $location): string;

    /**
     * @return resource
     * @throws UnableToReadFile
     * @throws FilesystemError
     */
    public function readStream(string $location);

    /**
     * @throws FilesystemError
     */
    public function listContents(string $location, bool $recursive = false): DirectoryListing;

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemError
     */
    public function lastModified(string $path): int;

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemError
     */
    public function fileSize(string $path): int;

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemError
     */
    public function mimeType(string $path): string;

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemError
     */
    public function visibility(string $path): string;
}
