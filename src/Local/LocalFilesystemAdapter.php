<?php

declare(strict_types=1);

namespace League\Flysystem\Local;

use DirectoryIterator;
use FilesystemIterator;
use finfo;
use Generator;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\SymbolicLinkEncountered;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\UnixVisibility\VisibilityConverter;
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
use function mkdir;
use function rename;
use function stream_copy_to_stream;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const FILEINFO_MIME_TYPE;
use const LOCK_EX;

class LocalFilesystemAdapter implements FilesystemAdapter
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
     * @var VisibilityConverter
     */
    private $visibility;

    public function __construct(
        string $location,
        VisibilityConverter $visibilityHandler = null,
        int $writeFlags = LOCK_EX,
        int $linkHandling = self::DISALLOW_LINKS
    ) {
        $this->prefixer = new PathPrefixer($location, DIRECTORY_SEPARATOR);
        $this->writeFlags = $writeFlags;
        $this->linkHandling = $linkHandling;
        $this->visibility = $visibilityHandler ?: new PortableVisibilityConverter();
        $this->ensureDirectoryExists($location, $this->visibility->defaultForDirectories());
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

        if ($visibility = $config->get(Config::OPTION_VISIBILITY)) {
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

        if ($visibility = $config->get(Config::OPTION_VISIBILITY)) {
            $this->setVisibility($location, (string) $visibility);
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

        $contents = $this->listDirectoryRecursively($location, RecursiveIteratorIterator::CHILD_FIRST);

        /** @var SplFileInfo $file */
        foreach ($contents as $file) {
            if ( ! $this->deleteFileInfoObject($file)) {
                throw UnableToDeleteDirectory::atLocation($prefix, "Unable to delete file at " . $file->getPathname());
            }
        }

        unset($contents);

        if ( ! @rmdir($location)) {
            throw UnableToDeleteDirectory::atLocation($prefix, error_get_last()['message'] ?? '');
        }
    }

    private function listDirectoryRecursively(
        string $path,
        int $mode = RecursiveIteratorIterator::SELF_FIRST
    ): Generator {
        yield from new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), $mode
        );
    }

    protected function deleteFileInfoObject(SplFileInfo $file): bool
    {
        switch ($file->getType()) {
            case 'dir':
                return @rmdir((string) $file->getRealPath());
            case 'link':
                return @unlink((string) $file->getPathname());
            default:
                return @unlink((string) $file->getRealPath());
        }
    }

    public function listContents(string $path, bool $deep): Generator
    {
        $location = $this->prefixer->prefixPath($path);

        if ( ! is_dir($location)) {
            return;
        }

        /** @var SplFileInfo[] $iterator */
        $iterator = $deep ? $this->listDirectoryRecursively($location) : $this->listDirectory($location);

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isLink()) {
                if ($this->linkHandling & self::SKIP_LINKS) {
                    continue;
                }
                throw SymbolicLinkEncountered::atLocation($fileInfo->getPathname());
            }

            $path = $this->prefixer->stripPrefix($fileInfo->getPathname());

            yield $fileInfo->isDir() ? new DirectoryAttributes($path) : new FileAttributes(
                $path, $fileInfo->getSize(), null, $fileInfo->getMTime()
            );
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $sourcePath = $this->prefixer->prefixPath($source);
        $destinationPath = $this->prefixer->prefixPath($destination);
        $this->ensureDirectoryExists(
            dirname($destinationPath),
            $this->resolveDirectoryVisibility($config->get('directory_visibility'))
        );

        if ( ! @rename($sourcePath, $destinationPath)) {
            throw UnableToMoveFile::fromLocationTo($sourcePath, $destinationPath);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $sourcePath = $this->prefixer->prefixPath($source);
        $destinationPath = $this->prefixer->prefixPath($destination);
        $this->ensureDirectoryExists(
            dirname($destinationPath),
            $this->resolveDirectoryVisibility($config->get('directory_visibility'))
        );

        if ( ! @copy($sourcePath, $destinationPath)) {
            throw UnableToCopyFile::fromLocationTo($sourcePath, $destinationPath);
        }
    }

    public function read(string $path): string
    {
        $location = $this->prefixer->prefixPath($path);
        error_clear_last();
        $contents = @file_get_contents($location);

        if ($contents === false) {
            throw UnableToReadFile::fromLocation($path, error_get_last()['message'] ?? '');
        }

        return $contents;
    }

    public function readStream(string $path)
    {
        $location = $this->prefixer->prefixPath($path);
        error_clear_last();
        $contents = @fopen($location, 'rb');

        if ($contents === false) {
            throw UnableToReadFile::fromLocation($path, error_get_last()['message'] ?? '');
        }

        return $contents;
    }

    protected function ensureDirectoryExists(string $dirname, int $visibility): void
    {
        if (is_dir($dirname)) {
            return;
        }

        error_clear_last();

        if ( ! @mkdir($dirname, $visibility, true)) {
            $mkdirError = error_get_last();
        }

        clearstatcache(false, $dirname);

        if ( ! is_dir($dirname)) {
            $errorMessage = isset($mkdirError['message']) ? $mkdirError['message'] : '';

            throw UnableToCreateDirectory::atLocation($dirname, $errorMessage);
        }
    }

    public function fileExists(string $location): bool
    {
        $location = $this->prefixer->prefixPath($location);

        return is_file($location);
    }

    public function createDirectory(string $path, Config $config): void
    {
        $location = $this->prefixer->prefixPath($path);
        $visibility = $config->get(Config::OPTION_VISIBILITY, $config->get(Config::OPTION_DIRECTORY_VISIBILITY));
        $permissions = $this->resolveDirectoryVisibility($visibility);

        if (is_dir($location)) {
            $this->setPermissions($location, $permissions);

            return;
        }

        error_clear_last();

        if ( ! @mkdir($location, $permissions, true)) {
            throw UnableToCreateDirectory::atLocation($path, error_get_last()['message'] ?? '');
        }
    }

    public function setVisibility(string $path, $visibility): void
    {
        $path = $this->prefixer->prefixPath($path);
        $visibility = is_dir($path) ? $this->visibility->forDirectory($visibility) : $this->visibility->forFile(
            $visibility
        );

        $this->setPermissions($path, $visibility);
    }

    public function visibility(string $path): FileAttributes
    {
        $location = $this->prefixer->prefixPath($path);
        clearstatcache(false, $location);
        error_clear_last();
        $fileperms = @fileperms($location);

        if ($fileperms === false) {
            throw UnableToRetrieveMetadata::visibility($path, error_get_last()['message'] ?? '');
        }

        $permissions = $fileperms & 0777;
        $visibility = $this->visibility->inverseForFile($permissions);

        return new FileAttributes($path, null, $visibility);
    }

    private function resolveDirectoryVisibility(?string $visibility): int
    {
        return $visibility === null ? $this->visibility->defaultForDirectories() : $this->visibility->forDirectory(
            $visibility
        );
    }

    public function mimeType(string $path): FileAttributes
    {
        $location = $this->prefixer->prefixPath($path);
        error_clear_last();
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = @$finfo->file($location);

        if ($mimeType === false) {
            throw UnableToRetrieveMetadata::mimeType($path, error_get_last()['message'] ?? '');
        }

        return new FileAttributes($path, null, null, null, $mimeType);
    }

    public function lastModified(string $path): FileAttributes
    {
        $location = $this->prefixer->prefixPath($path);
        error_clear_last();
        $lastModified = @filemtime($location);

        if ($lastModified === false) {
            throw UnableToRetrieveMetadata::lastModified($path, error_get_last()['message'] ?? '');
        }

        return new FileAttributes($path, null, null, $lastModified);
    }

    public function fileSize(string $path): FileAttributes
    {
        $location = $this->prefixer->prefixPath($path);
        error_clear_last();

        if (is_file($location) && ($fileSize = @filesize($location)) !== false) {
            return new FileAttributes($path, $fileSize);
        }

        throw UnableToRetrieveMetadata::fileSize($path, error_get_last()['message'] ?? '');
    }

    private function listDirectory(string $location): Generator
    {
        $iterator = new DirectoryIterator($location);

        foreach ($iterator as $item) {
            if ($item->isDot()) {
                continue;
            }

            yield $item;
        }
    }

    private function setPermissions(string $location, int $visibility): void
    {
        error_clear_last();
        if ( ! @chmod($location, $visibility)) {
            $extraMessage = error_get_last()['message'] ?? '';
            throw UnableToSetVisibility::atLocation($this->prefixer->stripPrefix($location), $extraMessage);
        }
    }
}
