<?php


namespace League\Flysystem;


interface EncryptedFilesystemAdapter
{
    /**
     * @throws FilesystemException
     */
    public function encryptedFileExists(string $path, string $encryptionKey): bool;

    /**
     * @throws UnableToWriteFile
     * @throws FilesystemException
     */
    public function encryptedWrite(string $path, string $contents, Config $config, string $encryptionKey): void;

    /**
     * @param resource $contents
     *
     * @throws UnableToWriteFile
     * @throws FilesystemException
     */
    public function encryptedWriteStream(string $path, $contents, Config $config, string $encryptionKey): void;

    /**
     * @throws UnableToReadFile
     * @throws FilesystemException
     */
    public function encryptedRead(string $path, string $encryptionKey): string;

    /**
     * @return resource
     *
     * @throws UnableToReadFile
     * @throws FilesystemException
     */
    public function encryptedReadStream(string $path, string $encryptionKey);

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function encryptedMimeType(string $path, string $encryptionKey): FileAttributes;

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function encryptedLastModified(string $path, string $encryptionKey): FileAttributes;

    /**
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function encryptedFileSize(string $path, string $encryptionKey): FileAttributes;

    /**
     * @throws UnableToMoveFile
     * @throws FilesystemException
     */
    public function encryptedMove(string $source, string $destination, Config $config, string $sourceKey, ?string $destinationKey = null): void;

    /**
     * @throws UnableToCopyFile
     * @throws FilesystemException
     */
    public function encryptedCopy(string $source, string $destination, Config $config, string $sourceKey, ?string $destinationKey = null): void;
}
