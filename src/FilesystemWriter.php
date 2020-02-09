<?php

declare(strict_types=1);

namespace League\Flysystem;

interface FilesystemWriter
{
    /**
     * @throws UnableToWriteFile
     * @throws FilesystemError
     */
    public function write(string $location, string $contents, array $config = []): void;

    /**
     * @throws UnableToWriteFile
     * @throws FilesystemError
     */
    public function writeStream(string $location, $contents, array $config = []): void;

    /**
     * @throws UnableToSetVisibility
     * @throws FilesystemError
     */
    public function setVisibility(string $path, string $visibility): void;

    /**
     * @throws UnableToDeleteFile
     * @throws FilesystemError
     */
    public function delete(string $location): void;

    /**
     * @throws UnableToDeleteDirectory
     * @throws FilesystemError
     */
    public function deleteDirectory(string $location): void;

    /**
     * @throws UnableToCreateDirectory
     * @throws FilesystemError
     */
    public function createDirectory(string $location, array $config = []): void;

    /**
     * @throws UnableToMoveFile
     * @throws FilesystemError
     */
    public function move(string $source, string $destination, array $config = []): void;

    /**
     * @throws UnableToCopyFile
     * @throws FilesystemError
     */
    public function copy(string $source, string $destination, array $config = []): void;
}
