<?php

declare(strict_types=1);

namespace League\Flysystem;

use Generator;

interface FilesystemAdapter
{
    public function fileExists(string $path): bool;

    /**
     * @throws UnableToWriteFile
     */
    public function write(string $path, string $contents, Config $config): void;

    /**
     * @throws UnableToWriteFile
     */
    public function writeStream(string $path, $contents, Config $config): void;

    /**
     * @throws UnableToReadFile
     */
    public function read(string $path): string;

    /**
     * @throws UnableToReadFile
     */
    public function readStream(string $path);

    /**
     * @throws UnableToDeleteFile
     */
    public function delete(string $path): void;

    /**
     * @throws UnableToDeleteDirectory
     */
    public function deleteDirectory(string $path): void;

    /**
     * @throws UnableToCreateDirectory
     */
    public function createDirectory(string $path, Config $config): void;

    /**
     * @throws InvalidVisibilityProvided
     */
    public function setVisibility(string $path, $visibility): void;

    /**
     * @throws UnableToRetrieveMetadata
     */
    public function visibility(string $path): FileAttributes;

    /**
     * @throws UnableToRetrieveMetadata
     */
    public function mimeType(string $path): FileAttributes;

    /**
     * @throws UnableToRetrieveMetadata
     */
    public function lastModified(string $path): FileAttributes;

    /**
     * @throws UnableToRetrieveMetadata
     */
    public function fileSize(string $path): FileAttributes;

    public function listContents(string $path, bool $recursive): Generator;

    /**
     * @throws UnableToMoveFile
     */
    public function move(string $source, string $destination, Config $config): void;

    /**
     * @throws UnableToCopyFile
     */
    public function copy(string $source, string $destination, Config $config): void;
}
