<?php

declare(strict_types=1);

namespace League\Flysystem;

/**
 * This interface contains everything to read from and inspect
 * a filesystem. All methods containing are non-destructive.
 */
interface FilesystemReader
{
    public const LIST_SHALLOW = false;
    public const LIST_DEEP = true;

    /**
     * @throws UnableToCheckFileExistence
     */
    public function fileExists(string $location): bool;

    /**
     * @throws UnableToReadFile
     */
    public function read(string $location): string;

    /**
     * @return resource
     *
     * @throws UnableToReadFile
     */
    public function readStream(string $location);

    /**
     * @return DirectoryListing<StorageAttributes>
     */
    public function listContents(string $location, bool $deep = self::LIST_SHALLOW): DirectoryListing;

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
