<?php

declare(strict_types=1);

namespace League\Flysystem\ZipArchive;

use Generator;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
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
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use Throwable;
use ZipArchive;

use function fclose;
use function fopen;
use function rewind;
use function stream_copy_to_stream;

final class ZipArchiveAdapter implements FilesystemAdapter
{
    private PathPrefixer $pathPrefixer;
    private MimeTypeDetector$mimeTypeDetector;
    private VisibilityConverter $visibility;

    public function __construct(
        private ZipArchiveProvider $zipArchiveProvider,
        string $root = '',
        ?MimeTypeDetector $mimeTypeDetector = null,
        ?VisibilityConverter $visibility = null,
        private bool $detectMimeTypeUsingPath = false,
    ) {
        $this->pathPrefixer = new PathPrefixer(ltrim($root, '/'));
        $this->mimeTypeDetector = $mimeTypeDetector ?? new FinfoMimeTypeDetector();
        $this->visibility = $visibility ?? new PortableVisibilityConverter();
    }

    public function fileExists(string $path): bool
    {
        $archive = $this->zipArchiveProvider->createZipArchive();
        $fileExists = $archive->locateName($this->pathPrefixer->prefixPath($path)) !== false;
        $archive->close();

        return $fileExists;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->ensureParentDirectoryExists($path, $config);
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($path, 'creating parent directory failed', $exception);
        }

        $archive = $this->zipArchiveProvider->createZipArchive();
        $prefixedPath = $this->pathPrefixer->prefixPath($path);

        if ( ! $archive->addFromString($prefixedPath, $contents)) {
            throw UnableToWriteFile::atLocation($path, 'writing the file failed');
        }

        $archive->close();
        $archive = $this->zipArchiveProvider->createZipArchive();

        $visibility = $config->get(Config::OPTION_VISIBILITY);
        $visibilityResult = $visibility === null
            || $this->setVisibilityAttribute($prefixedPath, $visibility, $archive);
        $archive->close();

        if ($visibilityResult === false) {
            throw UnableToWriteFile::atLocation($path, 'setting visibility failed');
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $contents = stream_get_contents($contents);

        if ($contents === false) {
            throw UnableToWriteFile::atLocation($path, 'Could not get contents of given resource.');
        }

        $this->write($path, $contents, $config);
    }

    public function read(string $path): string
    {
        $archive = $this->zipArchiveProvider->createZipArchive();
        $contents = $archive->getFromName($this->pathPrefixer->prefixPath($path));
        $statusString = $archive->getStatusString();
        $archive->close();

        if ($contents === false) {
            throw UnableToReadFile::fromLocation($path, $statusString);
        }

        return $contents;
    }

    public function readStream(string $path)
    {
        $archive = $this->zipArchiveProvider->createZipArchive();
        $resource = $archive->getStream($this->pathPrefixer->prefixPath($path));

        if ($resource === false) {
            $status = $archive->getStatusString();
            $archive->close();
            throw UnableToReadFile::fromLocation($path, $status);
        }

        $stream = fopen('php://temp', 'w+b');
        stream_copy_to_stream($resource, $stream);
        rewind($stream);
        fclose($resource);

        return $stream;
    }

    public function delete(string $path): void
    {
        $prefixedPath = $this->pathPrefixer->prefixPath($path);
        $zipArchive = $this->zipArchiveProvider->createZipArchive();
        $success = $zipArchive->locateName($prefixedPath) === false || $zipArchive->deleteName($prefixedPath);
        $statusString = $zipArchive->getStatusString();
        $zipArchive->close();

        if ( ! $success) {
            throw UnableToDeleteFile::atLocation($path, $statusString);
        }
    }

    public function deleteDirectory(string $path): void
    {
        $archive = $this->zipArchiveProvider->createZipArchive();
        $prefixedPath = $this->pathPrefixer->prefixDirectoryPath($path);

        for ($i = $archive->numFiles; $i > 0; $i--) {
            if (($stats = $archive->statIndex($i)) === false) {
                continue;
            }

            $itemPath = $stats['name'];

            if (strpos($itemPath, $prefixedPath) !== 0) {
                continue;
            }

            if ( ! $archive->deleteIndex($i)) {
                $statusString = $archive->getStatusString();
                $archive->close();
                throw UnableToDeleteDirectory::atLocation($path, $statusString);
            }
        }

        $archive->deleteName($prefixedPath);

        $archive->close();
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->ensureDirectoryExists($path, $config);
        } catch (Throwable $exception) {
            throw UnableToCreateDirectory::dueToFailure($path, $exception);
        }
    }

    public function directoryExists(string $path): bool
    {
        $archive = $this->zipArchiveProvider->createZipArchive();
        $location = $this->pathPrefixer->prefixDirectoryPath($path);

        return $archive->statName($location) !== false;
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $archive = $this->zipArchiveProvider->createZipArchive();
        $location = $this->pathPrefixer->prefixPath($path);
        $stats = $archive->statName($location) ?: $archive->statName($location . '/');

        if ($stats === false) {
            $statusString = $archive->getStatusString();
            $archive->close();
            throw UnableToSetVisibility::atLocation($path, $statusString);
        }

        if ( ! $this->setVisibilityAttribute($stats['name'], $visibility, $archive)) {
            $statusString1 = $archive->getStatusString();
            $archive->close();
            throw UnableToSetVisibility::atLocation($path, $statusString1);
        }

        $archive->close();
    }

    public function visibility(string $path): FileAttributes
    {
        $opsys = null;
        $attr = null;
        $archive = $this->zipArchiveProvider->createZipArchive();
        $archive->getExternalAttributesName(
            $this->pathPrefixer->prefixPath($path),
            $opsys,
            $attr
        );
        $archive->close();

        if ($opsys !== ZipArchive::OPSYS_UNIX || $attr === null) {
            throw UnableToRetrieveMetadata::visibility($path);
        }

        return new FileAttributes(
            $path,
            null,
            $this->visibility->inverseForFile($attr >> 16)
        );
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
        $zipArchive = $this->zipArchiveProvider->createZipArchive();
        $stats = $zipArchive->statName($this->pathPrefixer->prefixPath($path));
        $statusString = $zipArchive->getStatusString();
        $zipArchive->close();

        if ($stats === false) {
            throw UnableToRetrieveMetadata::lastModified($path, $statusString);
        }

        return new FileAttributes($path, null, null, $stats['mtime']);
    }

    public function fileSize(string $path): FileAttributes
    {
        $archive = $this->zipArchiveProvider->createZipArchive();
        $stats = $archive->statName($this->pathPrefixer->prefixPath($path));
        $statusString = $archive->getStatusString();
        $archive->close();

        if ($stats === false) {
            throw UnableToRetrieveMetadata::fileSize($path, $statusString);
        }

        if ($this->isDirectoryPath($stats['name'])) {
            throw UnableToRetrieveMetadata::fileSize($path, 'It\'s a directory.');
        }

        return new FileAttributes($path, $stats['size'], null, null);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $archive = $this->zipArchiveProvider->createZipArchive();
        $location = $this->pathPrefixer->prefixDirectoryPath($path);
        $items = [];

        for ($i = 0; $i < $archive->numFiles; $i++) {
            $stats = $archive->statIndex($i);
            // @codeCoverageIgnoreStart
            if ($stats === false) {
                continue;
            }
            // @codeCoverageIgnoreEnd

            $itemPath = $stats['name'];

            if (
                $location === $itemPath
                || ($deep && $location !== '' && strpos($itemPath, $location) !== 0)
                || ($deep === false && ! $this->isAtRootDirectory($location, $itemPath))
            ) {
                continue;
            }

            $items[] = $this->isDirectoryPath($itemPath)
                ? new DirectoryAttributes(
                    $this->pathPrefixer->stripDirectoryPrefix($itemPath),
                    null,
                    $stats['mtime']
                )
                : new FileAttributes(
                    $this->pathPrefixer->stripPrefix($itemPath),
                    $stats['size'],
                    null,
                    $stats['mtime']
                );
        }

        $archive->close();

        return $this->yieldItemsFrom($items);
    }

    private function yieldItemsFrom(array $items): Generator
    {
        yield from $items;
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->ensureParentDirectoryExists($destination, $config);
        } catch (Throwable $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }

        $archive = $this->zipArchiveProvider->createZipArchive();
        $renamed = $archive->renameName(
            $this->pathPrefixer->prefixPath($source),
            $this->pathPrefixer->prefixPath($destination)
        );

        if ($renamed === false) {
            throw UnableToMoveFile::fromLocationTo($source, $destination);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $readStream = $this->readStream($source);
            $this->writeStream($destination, $readStream, $config);
        } catch (Throwable $exception) {
            if (isset($readStream)) {
                @fclose($readStream);
            }

            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    private function ensureParentDirectoryExists(string $path, Config $config): void
    {
        $dirname = dirname($path);

        if ($dirname === '' || $dirname === '.') {
            return;
        }

        $this->ensureDirectoryExists($dirname, $config);
    }

    private function ensureDirectoryExists(string $dirname, Config $config): void
    {
        $visibility = $config->get(Config::OPTION_DIRECTORY_VISIBILITY);
        $archive = $this->zipArchiveProvider->createZipArchive();
        $prefixedDirname = $this->pathPrefixer->prefixDirectoryPath($dirname);
        $parts = array_filter(explode('/', trim($prefixedDirname, '/')));
        $dirPath = '';

        foreach ($parts as $part) {
            $dirPath .= $part . '/';
            $info = $archive->statName($dirPath);

            if ($info === false && $archive->addEmptyDir($dirPath) === false) {
                throw UnableToCreateDirectory::atLocation($dirname);
            }

            if ($visibility === null) {
                continue;
            }

            if ( ! $this->setVisibilityAttribute($dirPath, $visibility, $archive)) {
                $archive->close();
                throw UnableToCreateDirectory::atLocation($dirname, 'Unable to set visibility.');
            }
        }

        $archive->close();
    }

    private function isDirectoryPath(string $path): bool
    {
        return substr($path, -1) === '/';
    }

    private function isAtRootDirectory(string $directoryRoot, string $path): bool
    {
        $dirname = dirname($path);

        if ('' === $directoryRoot && '.' === $dirname) {
            return true;
        }

        return $directoryRoot === (rtrim($dirname, '/') . '/');
    }

    private function setVisibilityAttribute(string $statsName, string $visibility, ZipArchive $archive): bool
    {
        $visibility = $this->isDirectoryPath($statsName)
            ? $this->visibility->forDirectory($visibility)
            : $this->visibility->forFile($visibility);

        return $archive->setExternalAttributesName($statsName, ZipArchive::OPSYS_UNIX, $visibility << 16);
    }
}
