<?php

declare(strict_types=1);

namespace League\Flysystem\PHPSecLibV2;

use Generator;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToUpdateFile;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use League\Flysystem\Visibility;
use phpseclib\Net\SFTP;
use Throwable;

class SftpFilesystem implements FilesystemAdapter
{
    /**
     * @var ConnectionProvider
     */
    private $connectionProvider;

    /**
     * @var VisibilityConverter
     */
    private $visibilityConverter;

    public function __construct(ConnectionProvider $connectionProvider, VisibilityConverter $visibilityConverter = null)
    {
        $this->connectionProvider = $connectionProvider;
        $this->visibilityConverter = $visibilityConverter ?: new PortableVisibilityConverter();
    }

    public function fileExists(string $path): bool
    {
        return $this->connectionProvider->provideConnection()->is_file($path);
    }

    private function upload(string $path, $contents, Config $config): void
    {
        $this->ensureParentDirectoryExists($path, $config);
        $connection = $this->connectionProvider->provideConnection();

        if ( ! $connection->put($path, $contents, SFTP::SOURCE_STRING)) {
            throw UnableToWriteFile::atLocation($path, 'not able to write the file');
        }

        if ( ! ($visibility = $config->get(Config::OPTION_VISIBILITY))) {
            return;
        }
    }

    private function ensureParentDirectoryExists(string $path, Config $config): void
    {
        $parentDirectory = dirname($path);

        if (empty($parentDirectory) || $parentDirectory === '') {
            return;
        }

        /** @var string $visibility */
        $visibility = $config->get(Config::OPTION_DIRECTORY_VISIBILITY, Visibility::PRIVATE);
        $this->makeDirectory($parentDirectory, $visibility);
    }

    private function makeDirectory(string $directory, string $visibility): void
    {
        $connection = $this->connectionProvider->provideConnection();

        if ($connection->is_dir($directory)) {
            return;
        }

        $mode = $this->visibilityConverter->forDirectory($visibility);

        if ( ! $connection->mkdir($directory, $mode, true)) {
            throw UnableToCreateDirectory::atLocation($directory);
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->upload($path, $contents, $config);
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($path, '', $exception);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        try {
            $this->upload($path, $contents, $config);
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($path, '', $exception);
        }
    }

    public function update(string $path, string $contents, Config $config): void
    {
        try {
            $this->upload($path, $contents, $config);
        } catch (Throwable $exception) {
            throw UnableToUpdateFile::atLocation($path, '', $exception);
        }
    }

    public function updateStream(string $path, $contents, Config $config): void
    {
        try {
            $this->upload($path, $contents, $config);
        } catch (Throwable $exception) {
            throw UnableToUpdateFile::atLocation($path, '', $exception);
        }
    }

    public function read(string $path): string
    {
        $connection = $this->connectionProvider->provideConnection();
        $contents = $connection->get($path);

        if ( ! is_string($contents)) {
            throw UnableToReadFile::fromLocation($path);
        }

        return $contents;
    }

    public function readStream(string $path)
    {
        $connection = $this->connectionProvider->provideConnection();
        $readStream = fopen('php://temp', 'w+');

        if ( ! $connection->get($path, $readStream)) {
            fclose($readStream);
            throw UnableToReadFile::fromLocation($path);
        }

        rewind($readStream);

        return $readStream;
    }

    public function delete(string $path): void
    {
    }

    public function deleteDirectory(string $path): void
    {
    }

    public function createDirectory(string $path, Config $config): void
    {
    }

    public function setVisibility(string $path, $visibility): void
    {
    }

    public function visibility(string $path): FileAttributes
    {
    }

    public function mimeType(string $path): FileAttributes
    {
    }

    public function lastModified(string $path): FileAttributes
    {
    }

    public function fileSize(string $path): FileAttributes
    {
    }

    public function listContents(string $path, bool $recursive): Generator
    {
    }

    public function move(string $source, string $destination, Config $config): void
    {
    }

    public function copy(string $source, string $destination, Config $config): void
    {
    }
}
