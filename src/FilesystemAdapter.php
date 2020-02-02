<?php

declare(strict_types=1);

namespace League\Flysystem;

use Generator;

interface FilesystemAdapter
{
    /**
     * @throws FilesystemError
     */
    public function fileExists(string $path): bool;

    /**
     * @throws UnableToWriteFile
     * @throws FilesystemError
     */
    public function write(string $path, string $contents, Config $config): void;

    /**
     * @throws UnableToWriteFile
     * @throws FilesystemError
     */
    public function writeStream(string $path, $contents, Config $config): void;

    /**
     * @throws UnableToReadFile
     * @throws FilesystemError
     */
    public function read(string $path): string;

    /**
     * @throws UnableToReadFile
     * @throws FilesystemError
     */
    public function readStream(string $path);

    /**
     * @throws UnableToDeleteFile
     * @throws FilesystemError
     */
    public function delete(string $path): void;

    /**
     * @throws UnableToDeleteDirectory
     * @throws FilesystemError
     */
    public function deleteDirectory(string $path): void;

    /**
     * @throws UnableToCreateDirectory
     * @throws FilesystemError
     */
    public function createDirectory(string $path, Config $config): void;

    /**
     * @throws InvalidVisibilityProvided
     * @throws FilesystemError
     */
    public function setVisibility(string $path, $visibility): void;

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemError
     */
    public function visibility(string $path): FileAttributes;

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemError
     */
    public function mimeType(string $path): FileAttributes;

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemError
     */
    public function lastModified(string $path): FileAttributes;

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemError
     */
    public function fileSize(string $path): FileAttributes;

    /**
     * @throws FilesystemError
     */
    public function listContents(string $path, bool $recursive): Generator;

    /**
     * @throws UnableToMoveFile
     * @throws FilesystemError
     */
    public function move(string $source, string $destination, Config $config): void;

    /**
     * @throws UnableToCopyFile
     * @throws FilesystemError
     */
    public function copy(string $source, string $destination, Config $config): void;
}
