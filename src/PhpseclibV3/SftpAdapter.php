<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV3;

use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\PathPrefixer;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToListContents;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use phpseclib3\Net\SFTP;
use Throwable;

use UnexpectedValueException;
use function rtrim;

class SftpAdapter implements FilesystemAdapter
{
    private const UNEXPECTED_SFTP_PACKET_MESSAGE = 'SFTP server respond an unexpected packet.';
    private VisibilityConverter $visibilityConverter;
    private PathPrefixer        $prefixer;
    private MimeTypeDetector    $mimeTypeDetector;

    public function __construct(
        private ConnectionProvider $connectionProvider,
        string                     $root,
        VisibilityConverter        $visibilityConverter = null,
        MimeTypeDetector           $mimeTypeDetector = null,
        private bool               $detectMimeTypeUsingPath = false,
    ) {
        $this->prefixer = new PathPrefixer($root);
        $this->visibilityConverter = $visibilityConverter ?? new PortableVisibilityConverter();
        $this->mimeTypeDetector = $mimeTypeDetector ?? new FinfoMimeTypeDetector();
    }

    public function fileExists(string $path): bool
    {
        $location = $this->prefixer->prefixPath($path);
        $connection = $this->connectionProvider->provideConnection();
        try {
            return $connection->is_file($location);
        } catch (UnexpectedValueException $exception) {
            $connection->disconnect();
            throw UnableToCheckFileExistence::forLocation($path, $exception);
        } catch (Throwable $exception) {
            throw UnableToCheckFileExistence::forLocation($path, $exception);
        }
    }

    public function disconnect(): void
    {
        $this->connectionProvider->disconnect();
    }

    public function directoryExists(string $path): bool
    {
        $location = $this->prefixer->prefixDirectoryPath($path);
        $connection = $this->connectionProvider->provideConnection();
        try {
            return $connection->is_dir($location);
        } catch (UnexpectedValueException $exception) {
            $connection->disconnect();
            throw UnableToCheckDirectoryExistence::forLocation($path, $exception);
        } catch (Throwable $exception) {
            throw UnableToCheckDirectoryExistence::forLocation($path, $exception);
        }
    }

    /**
     * @param string          $path
     * @param string|resource $contents
     * @param Config          $config
     *
     * @throws FilesystemException
     */
    private function upload(string $path, $contents, Config $config): void
    {
        $this->ensureParentDirectoryExists($path, $config);
        $connection = $this->connectionProvider->provideConnection();
        try {
            $location = $this->prefixer->prefixPath($path);

            if ( ! $connection->put($location, $contents, SFTP::SOURCE_STRING)) {
                throw UnableToWriteFile::atLocation($path, $connection->getLastSFTPError());
            }

            if ($visibility = $config->get(Config::OPTION_VISIBILITY)) {
                $this->setVisibility($path, $visibility);
            }
        } catch (UnexpectedValueException $exception) {
            $connection->disconnect();
            throw UnableToWriteFile::atLocation($path, self::UNEXPECTED_SFTP_PACKET_MESSAGE, $exception);
        }
    }

    private function ensureParentDirectoryExists(string $path, Config $config): void
    {
        $parentDirectory = dirname($path);

        if ($parentDirectory === '' || $parentDirectory === '.') {
            return;
        }

        /** @var string $visibility */
        $visibility = $config->get(Config::OPTION_DIRECTORY_VISIBILITY);
        $this->makeDirectory($parentDirectory, $visibility);
    }

    private function makeDirectory(string $directory, ?string $visibility): void
    {
        $location = $this->prefixer->prefixPath($directory);
        $connection = $this->connectionProvider->provideConnection();

        try {
            if ($connection->is_dir($location)) {
                return;
            }

            $mode = $visibility ? $this->visibilityConverter->forDirectory(
                $visibility
            ) : $this->visibilityConverter->defaultForDirectories();

            if ( ! $connection->mkdir($location, $mode, true) && ! $connection->is_dir($location)) {
                throw UnableToCreateDirectory::atLocation($directory, $connection->getLastSFTPError());
            }
        } catch (UnexpectedValueException $exception) {
            $connection->disconnect();
            throw UnableToCreateDirectory::atLocation($directory, self::UNEXPECTED_SFTP_PACKET_MESSAGE, $exception);
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->upload($path, $contents, $config);
        } catch (UnableToWriteFile $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        try {
            $this->upload($path, $contents, $config);
        } catch (UnableToWriteFile $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function read(string $path): string
    {
        $location = $this->prefixer->prefixPath($path);
        $connection = $this->connectionProvider->provideConnection();
        try {
            $contents = $connection->get($location);

            if ( ! is_string($contents)) {
                throw UnableToReadFile::fromLocation($path, $connection->getLastSFTPError());
            }

            return $contents;
        } catch (UnexpectedValueException $exception) {
            $connection->disconnect();
            throw UnableToReadFile::fromLocation($path, self::UNEXPECTED_SFTP_PACKET_MESSAGE, $exception);
        } catch (Throwable $exception) {
            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function readStream(string $path)
    {
        $location = $this->prefixer->prefixPath($path);
        $connection = $this->connectionProvider->provideConnection();
        /** @var resource $readStream */
        $readStream = fopen('php://temp', 'w+');

        try {
            if ( ! $connection->get($location, $readStream)) {
                fclose($readStream);
                throw UnableToReadFile::fromLocation($path, $connection->getLastSFTPError());
            }

            rewind($readStream);

            return $readStream;
        } catch (UnexpectedValueException $exception) {
            $connection->disconnect();
            fclose($readStream);
            throw UnableToReadFile::fromLocation($path, self::UNEXPECTED_SFTP_PACKET_MESSAGE, $exception);
        } catch (Throwable $exception) {
            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function delete(string $path): void
    {
        $location = $this->prefixer->prefixPath($path);
        $connection = $this->connectionProvider->provideConnection();
        try {
            $connection->delete($location);
        } catch (UnexpectedValueException $exception) {
            $connection->disconnect();
            throw UnableToDeleteFile::atLocation($path, self::UNEXPECTED_SFTP_PACKET_MESSAGE, $exception);
        } catch (Throwable $exception) {
            throw UnableToDeleteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function deleteDirectory(string $path): void
    {
        $location = rtrim($this->prefixer->prefixPath($path), '/') . '/';
        $connection = $this->connectionProvider->provideConnection();
        try {
            $connection->delete($location);
            $connection->rmdir($location);
        } catch (UnexpectedValueException $exception) {
            $connection->disconnect();
            throw UnableToDeleteDirectory::atLocation($path, self::UNEXPECTED_SFTP_PACKET_MESSAGE, $exception);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        $this->makeDirectory($path, $config->get(Config::OPTION_DIRECTORY_VISIBILITY, $config->get(Config::OPTION_VISIBILITY)));
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $location = $this->prefixer->prefixPath($path);
        $connection = $this->connectionProvider->provideConnection();
        $mode = $this->visibilityConverter->forFile($visibility);

        try {
            if ( ! $connection->chmod($mode, $location, false)) {
                throw UnableToSetVisibility::atLocation($path, $connection->getLastSFTPError());
            }
        } catch (UnexpectedValueException $exception) {
            $connection->disconnect();
            throw UnableToSetVisibility::atLocation($path, self::UNEXPECTED_SFTP_PACKET_MESSAGE, $exception);
        }
    }

    private function fetchFileMetadata(string $path, string $type): FileAttributes
    {
        $location = $this->prefixer->prefixPath($path);
        $connection = $this->connectionProvider->provideConnection();
        try {
            $stat = $connection->stat($location);

            if ( ! is_array($stat)) {
                throw UnableToRetrieveMetadata::create($path, $type);
            }

            $attributes = $this->convertListingToAttributes($path, $stat);

            if ( ! $attributes instanceof FileAttributes) {
                throw UnableToRetrieveMetadata::create($path, $type, 'path is not a file');
            }

            return $attributes;
        } catch (UnexpectedValueException $exception) {
            $connection->disconnect();
            throw UnableToRetrieveMetadata::create($path, $type, self::UNEXPECTED_SFTP_PACKET_MESSAGE, $exception);
        }
    }

    public function mimeType(string $path): FileAttributes
    {
        try {
            $mimetype = $this->detectMimeTypeUsingPath
                ? $this->mimeTypeDetector->detectMimeTypeFromPath($path)
                : $this->mimeTypeDetector->detectMimeType($path, $this->read($path));
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::mimeType($path, $exception->getMessage(), $exception);
        }

        if ($mimetype === null) {
            throw UnableToRetrieveMetadata::mimeType($path, 'Unknown.');
        }

        return new FileAttributes($path, null, null, null, $mimetype);
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->fetchFileMetadata($path, FileAttributes::ATTRIBUTE_LAST_MODIFIED);
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->fetchFileMetadata($path, FileAttributes::ATTRIBUTE_FILE_SIZE);
    }

    public function visibility(string $path): FileAttributes
    {
        return $this->fetchFileMetadata($path, FileAttributes::ATTRIBUTE_VISIBILITY);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $connection = $this->connectionProvider->provideConnection();
        try {
            $location = $this->prefixer->prefixPath(rtrim($path, '/')) . '/';
            $listing = $connection->rawlist($location, false);

            if (false === $listing) {
                return;
            }

            foreach ($listing as $filename => $attributes) {
                if ($filename === '.' || $filename === '..') {
                    continue;
                }

                // Ensure numeric keys are strings.
                $filename = (string) $filename;
                $path = $this->prefixer->stripPrefix($location . ltrim($filename, '/'));
                $attributes = $this->convertListingToAttributes($path, $attributes);
                yield $attributes;

                if ($deep && $attributes->isDir()) {
                    foreach ($this->listContents($attributes->path(), true) as $child) {
                        yield $child;
                    }
                }
            }
        } catch (UnexpectedValueException $exception) {
            $connection->disconnect();
            throw UnableToListContents::atLocation($path, $deep, $exception);
        }
    }

    private function convertListingToAttributes(string $path, array $attributes): StorageAttributes
    {
        $permissions = $attributes['mode'] & 0777;
        $lastModified = $attributes['mtime'] ?? null;

        if (($attributes['type'] ?? null) === NET_SFTP_TYPE_DIRECTORY) {
            return new DirectoryAttributes(
                ltrim($path, '/'),
                $this->visibilityConverter->inverseForDirectory($permissions),
                $lastModified
            );
        }

        return new FileAttributes(
            $path,
            $attributes['size'],
            $this->visibilityConverter->inverseForFile($permissions),
            $lastModified
        );
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $sourceLocation = $this->prefixer->prefixPath($source);
        $destinationLocation = $this->prefixer->prefixPath($destination);
        $connection = $this->connectionProvider->provideConnection();

        try {
            $this->ensureParentDirectoryExists($destination, $config);

            if ( ! $connection->rename($sourceLocation, $destinationLocation)) {
                throw UnableToMoveFile::fromLocationTo($source, $destination);
            }
        } catch (UnableToMoveFile $exception) {
            throw $exception;
        } catch (UnexpectedValueException $exception) {
            $connection->disconnect();
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        } catch (Throwable $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $readStream = $this->readStream($source);
            $visibility = $config->get(Config::OPTION_VISIBILITY);

            if ($visibility === null && $config->get(Config::OPTION_RETAIN_VISIBILITY, true)) {
                $config = $config->withSetting(Config::OPTION_VISIBILITY, $this->visibility($source)->visibility());
            }

            $this->writeStream($destination, $readStream, $config);
        } catch (Throwable $exception) {
            if (isset($readStream) && is_resource($readStream)) {
                @fclose($readStream);
            }
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }
}
