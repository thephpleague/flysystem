<?php

declare(strict_types=1);

namespace League\Flysystem;

interface FilesystemWriter
{
    /**
     * @throws UnableToWriteFile
     */
    public function write(string $location, string $contents, array $config = []): void;

    /**
     * @throws UnableToWriteFile
     */
    public function writeStream(string $location, $contents, array $config = []): void;

    /**
     * @throws UnableToDeleteFile
     */
    public function delete(string $location): void;

    /**
     * @throws UnableToDeleteDirectory
     */
    public function deleteDirectory(string $location): void;

    /**
     * @throws UnableToCreateDirectory
     */
    public function createDirectory(string $location, array $config = []): void;

    /**
     * @throws UnableToMoveFile
     */
    public function move(string $source, string $destination, array $config = []): void;

    /**
     * @throws UnableToCopyFile
     */
    public function copy(string $source, string $destination, array $config = []): void;
}
