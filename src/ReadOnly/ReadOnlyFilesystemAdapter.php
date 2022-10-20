<?php

namespace League\Flysystem\ReadOnly;

use DateTimeInterface;
use League\Flysystem\CalculateChecksumFromStream;
use League\Flysystem\ChecksumProvider;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToGeneratePublicUrl;
use League\Flysystem\UnableToGenerateTemporaryUrl;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;

class ReadOnlyFilesystemAdapter implements FilesystemAdapter, PublicUrlGenerator, ChecksumProvider, TemporaryUrlGenerator
{
    use CalculateChecksumFromStream;

    public function __construct(private FilesystemAdapter $adapter)
    {
    }

    public function fileExists(string $path): bool
    {
        return $this->adapter->fileExists($path);
    }

    public function directoryExists(string $path): bool
    {
        return $this->adapter->directoryExists($path);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        throw UnableToWriteFile::atLocation($path, 'This is a readonly adapter.');
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        throw UnableToWriteFile::atLocation($path, 'This is a readonly adapter.');
    }

    public function read(string $path): string
    {
        return $this->adapter->read($path);
    }

    public function readStream(string $path)
    {
        return $this->adapter->readStream($path);
    }

    public function delete(string $path): void
    {
        throw UnableToDeleteFile::atLocation($path, 'This is a readonly adapter.');
    }

    public function deleteDirectory(string $path): void
    {
        throw UnableToDeleteDirectory::atLocation($path, 'This is a readonly adapter.');
    }

    public function createDirectory(string $path, Config $config): void
    {
        throw UnableToCreateDirectory::atLocation($path, 'This is a readonly adapter.');
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'This is a readonly adapter.');
    }

    public function visibility(string $path): FileAttributes
    {
        return $this->adapter->visibility($path);
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->adapter->mimeType($path);
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->adapter->lastModified($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->adapter->fileSize($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        return $this->adapter->listContents($path, $deep);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        throw new UnableToMoveFile("Unable to move file from $source to $destination as this is a readonly adapter.");
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        throw new UnableToCopyFile("Unable to copy file from $source to $destination as this is a readonly adapter.");
    }

    public function publicUrl(string $path, Config $config): string
    {
        if ( ! $this->adapter instanceof PublicUrlGenerator) {
            throw UnableToGeneratePublicUrl::noGeneratorConfigured($path);
        }

        return $this->adapter->publicUrl($path, $config);
    }

    public function checksum(string $path, Config $config): string
    {
        if ($this->adapter instanceof ChecksumProvider) {
            return $this->adapter->checksum($path, $config);
        }

        return $this->calculateChecksumFromStream($path, $config);
    }

    public function temporaryUrl(string $path, DateTimeInterface $expiresAt, Config $config): string
    {
        if ( ! $this->adapter instanceof TemporaryUrlGenerator) {
            throw UnableToGenerateTemporaryUrl::noGeneratorConfigured($path);
        }

        return $this->adapter->temporaryUrl($path, $expiresAt, $config);
    }
}
