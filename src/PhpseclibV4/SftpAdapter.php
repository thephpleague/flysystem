<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV4;

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
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use phpseclib4\Exception\FileSystemException as SftpFileSystemException;
use phpseclib4\Net\SFTP;
use phpseclib4\Net\SFTP\FileType;
use Throwable;

use function rtrim;

class SftpAdapter implements FilesystemAdapter
{
    private VisibilityConverter $visibilityConverter;
    private PathPrefixer $prefixer;
    private MimeTypeDetector $mimeTypeDetector;

    public function __construct(
        private ConnectionProvider $connectionProvider,
        string $root,
        ?VisibilityConverter $visibilityConverter = null,
        ?MimeTypeDetector $mimeTypeDetector = null,
        private bool $detectMimeTypeUsingPath = false,
        private bool $disconnectOnDestruct = false,
    ) {
        $this->prefixer = new PathPrefixer($root);
        $this->visibilityConverter = $visibilityConverter ?? new PortableVisibilityConverter();
        $this->mimeTypeDetector = $mimeTypeDetector ?? new FinfoMimeTypeDetector();
    }

    public function fileExists(string $path): bool
    {
        $location = $this->prefixer->prefixPath($path);

        try {
            return $this->connectionProvider->provideConnection()->is_file($location);
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

        try {
            return $this->connectionProvider->provideConnection()->is_dir($location);
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
        $location = $this->prefixer->prefixPath($path);

        try {
            $connection->put($location, $contents, SFTP::SOURCE_STRING);
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($path, 'not able to write the file', $exception);
        }

        if ($visibility = $config->get(Config::OPTION_VISIBILITY)) {
            $this->setVisibility($path, $visibility);
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

        if ($connection->is_dir($location)) {
            return;
        }

        $mode = $visibility ? $this->visibilityConverter->forDirectory(
            $visibility
        ) : $this->visibilityConverter->defaultForDirectories();

        try {
            $connection->mkdir($location, $mode, true);
        } catch (Throwable $exception) {
            if ( ! $connection->is_dir($location)) {
                throw UnableToCreateDirectory::atLocation($directory, $exception->getMessage(), $exception);
            }
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
            return $connection->get($location);
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
            $connection->get($location, $readStream);
        } catch (Throwable $exception) {
            fclose($readStream);
            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }

        rewind($readStream);

        return $readStream;
    }

    public function delete(string $path): void
    {
        $location = $this->prefixer->prefixPath($path);
        $connection = $this->connectionProvider->provideConnection();
        $connection->delete($location);
    }

    public function deleteDirectory(string $path): void
    {
        $location = rtrim($this->prefixer->prefixPath($path), '/') . '/';
        $connection = $this->connectionProvider->provideConnection();
        $connection->delete($location);

        try {
            $connection->rmdir($location);
        } catch (SftpFileSystemException) {
            // The recursive delete() above already removes the directory itself, so rmdir()
            // usually fails here. phpseclib 3 returned false in that case, which was ignored too.
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
            $connection->chmod($location, $mode, false);
        } catch (Throwable $exception) {
            throw UnableToSetVisibility::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    private function fetchFileMetadata(string $path, string $type): FileAttributes
    {
        $location = $this->prefixer->prefixPath($path);
        $connection = $this->connectionProvider->provideConnection();

        try {
            $stat = $connection->stat($location);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::create($path, $type, $exception->getMessage(), $exception);
        }

        $attributes = $this->convertListingToAttributes($path, $stat);

        if ( ! $attributes instanceof FileAttributes) {
            throw UnableToRetrieveMetadata::create($path, $type, 'path is not a file');
        }

        return $attributes;
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
        $location = $this->prefixer->prefixPath(rtrim($path, '/')) . '/';

        try {
            $listing = $connection->rawlist($location, false);
        } catch (SftpFileSystemException) {
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
    }

    private function convertListingToAttributes(string $path, array $attributes): StorageAttributes
    {
        $permissions = $attributes['mode'] & 0777;
        $lastModified = $attributes['mtime'] ?? null;

        if (($attributes['type'] ?? null) === FileType::DIRECTORY) {
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
        } catch (Throwable $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }

        if ($sourceLocation === $destinationLocation) {
            return;
        }

        try {
            $connection->rename($sourceLocation, $destinationLocation);

            return;
        } catch (Throwable $exception) {
            // Overwrite existing file / dir
            if ( ! $connection->is_file($destinationLocation)) {
                throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
            }
        }

        try {
            $this->delete($destination);
            $connection->rename($sourceLocation, $destinationLocation);
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

    public function __destruct()
    {
        if ($this->disconnectOnDestruct) {
            $this->connectionProvider->disconnect();
        }
    }
}
