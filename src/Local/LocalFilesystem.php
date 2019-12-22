<?php

declare(strict_types=1);

namespace League\Flysystem\Local;

use FilesystemIterator;
use Generator;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToUpdateFile;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function chmod;
use function clearstatcache;
use function dirname;
use function error_clear_last;
use function error_get_last;
use function file_exists;
use function is_dir;
use function is_file;
use function rmdir;
use function stream_copy_to_stream;
use function unlink;
use function var_dump;

use const DIRECTORY_SEPARATOR;
use const LOCK_EX;

class LocalFilesystem implements FilesystemAdapter
{
    /**
     * @var int
     */
    public const SKIP_LINKS = 0001;
    /**
     * @var int
     */
    public const DISALLOW_LINKS = 0002;

    /**
     * @var PathPrefixer
     */
    private $prefixer;

    /**
     * @var int
     */
    private $writeFlags;

    /**
     * @var int
     */
    private $linkHandling;

    /**
     * @var string
     */
    private $directoryVisibility;

    /**
     * @var LocalVisibilityInterpreting
     */
    private $visibility;

    public function __construct(
        string $location,
        LocalVisibilityInterpreting $visibilityHandler = null,
        int $writeFlags = LOCK_EX,
        int $linkHandling = self::DISALLOW_LINKS,
        string $directoryVisibility = Visibility::PUBLIC
    ) {
        $this->prefixer = new PathPrefixer($location, DIRECTORY_SEPARATOR);
        $this->writeFlags = $writeFlags;
        $this->linkHandling = $linkHandling;
        $this->visibility = $visibilityHandler ?: new PublicAndPrivateVisibilityInterpreting();
        $this->ensureDirectoryExists($location, $this->visibility->defaultForDirectories());
        $this->directoryVisibility = $directoryVisibility;
    }

    public function write(string $location, string $contents, Config $config): void
    {
        $prefixedLocation = $this->prefixer->prefixPath($location);
        $this->ensureDirectoryExists(
            dirname($prefixedLocation),
            $this->resolveDirectoryVisibility($config->get('directory_visibility'))
        );
        error_clear_last();

        if (($size = @file_put_contents($prefixedLocation, $contents, $this->writeFlags)) === false) {
            throw UnableToWriteFile::atLocation($location, error_get_last()['message'] ?? '');
        }

        if ($visibility = $config->get('visibility')) {
            $this->setVisibility($location, (string) $visibility);
        }
    }

    public function writeStream(string $location, $contents, Config $config): void
    {
        $path = $this->prefixer->prefixPath($location);
        $this->ensureDirectoryExists(
            dirname($path),
            $this->resolveDirectoryVisibility($config->get('directory_visibility'))
        );

        error_clear_last();
        $stream = @fopen($path, 'w+b');

        if ( ! ($stream && stream_copy_to_stream($contents, $stream) && fclose($stream))) {
            $reason = error_get_last()['message'] ?? '';
            throw UnableToWriteFile::atLocation($path, $reason);
        }

        if ($visibility = $config->get('visibility')) {
            $this->setVisibility($location, (string) $visibility);
        }
    }

    public function update(string $location, string $contents, Config $config): void
    {
        try {
            $this->write($location, $contents, $config);
        } catch (UnableToWriteFile $exception) {
            throw UnableToUpdateFile::atLocation($location, $exception->reason());
        }
    }

    public function updateStream(string $location, $contents, Config $config): void
    {
        try {
            $this->writeStream($location, $contents, $config);
        } catch (UnableToWriteFile $exception) {
            throw UnableToUpdateFile::atLocation($location, $exception->reason());
        }
    }

    public function delete(string $path): void
    {
        $location = $this->prefixer->prefixPath($path);

        if ( ! file_exists($location)) {
            return;
        }

        error_clear_last();

        if ( ! @unlink($location)) {
            throw UnableToDeleteFile::atLocation($location, error_get_last()['message'] ?? '');
        }
    }

    public function deleteDirectory(string $prefix): void
    {
        $location = $this->prefixer->prefixPath($prefix);

        if ( ! is_dir($location)) {
            return;
        }

        $contents = $this->getRecursiveDirectoryIterator($location, RecursiveIteratorIterator::CHILD_FIRST);

        /** @var SplFileInfo $file */
        foreach ($contents as $file) {
            $this->guardAgainstUnreadableFileInfo($file);
            $this->deleteFileInfoObject($file);
        }

        unset($contents);

        if ( ! @rmdir($location)) {
            throw UnableToDeleteDirectory::atLocation($prefix, error_get_last()['message'] ?? '');
        }
    }

    private function getRecursiveDirectoryIterator(
        string $path,
        int $mode = RecursiveIteratorIterator::SELF_FIRST
    ): RecursiveIteratorIterator {
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), $mode
        );
    }

    protected function guardAgainstUnreadableFileInfo(SplFileInfo $file)
    {
        if ( ! $file->isReadable()) {
            throw UnreadableFileEncountered::atLocation($this->usablePathForFileInfo($file));
        }
    }

    private function usablePathForFileInfo(SplFileInfo $fileInfo): string
    {
        return $fileInfo->getType() === 'link' ? $fileInfo->getPathname() : $fileInfo->getRealPath();
    }

    protected function deleteFileInfoObject(SplFileInfo $file): void
    {
        switch ($file->getType()) {
            case 'dir':
                rmdir($file->getRealPath());
                break;
            case 'link':
                unlink($file->getPathname());
                break;
            default:
                unlink($file->getRealPath());
        }
    }

    public function listContents(string $location): Generator
    {
    }

    public function move(string $source, string $destination): void
    {
    }

    public function copy(string $source, string $destination): void
    {
    }

    public function read(string $location): string
    {
    }

    /**
     * @inheritDoc
     */
    public function readStream(string $location)
    {
    }

    protected function ensureDirectoryExists(string $dirname, int $visibility)
    {
        if ( ! is_dir($dirname)) {
            if ( ! @mkdir($dirname, $visibility, true)) {
                $mkdirError = error_get_last();
            }
            clearstatcache(false, $dirname);
            if ( ! is_dir($dirname)) {
                $errorMessage = isset($mkdirError['message']) ? $mkdirError['message'] : '';
                throw UnableToCreateDirectory::atLocation($dirname, $errorMessage);
            }
        }
    }

    public function fileExists(string $location): bool
    {
        $location = $this->prefixer->prefixPath($location);

        return is_file($location);
    }

    public function createDirectory(string $location, Config $config): void
    {
    }

    public function setVisibility(string $location, $visibility): void
    {
        $location = $this->prefixer->prefixPath($location);
        $visibility = is_dir($location) ? $this->visibility->forDirectory($visibility) : $this->visibility->forFile(
            $visibility
        );

        error_clear_last();
        if ( ! @chmod($location, $visibility)) {
            $extraMessage = error_get_last()['message'] ?? '';
            throw UnableToSetVisibility::atLocation($this->prefixer->stripPrefix($location), $extraMessage);
        }
    }

    public function visibility(string $location): string
    {
    }

    private function resolveDirectoryVisibility($visibility)
    {
        return $visibility === null ? $this->visibility->defaultForDirectories() : $this->visibility->forDirectory(
            $visibility
        );
    }

    public function mimeType(string $path): string
    {
    }

    public function lastModified(string $path): int
    {
    }

    public function fileSize(string $path): int
    {
    }
}
