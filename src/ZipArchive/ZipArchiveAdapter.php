<?php

declare(strict_types=1);

namespace League\Flysystem\ZipArchive;

use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
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

final class ZipArchiveAdapter implements FilesystemAdapter
{
    /** @var string */
    private $filename;
    /** @var ZipArchive|null */
    private $archive;
    /** @var ZipArchivePathNormalizer */
    private $pathNormalizer;
    /** @var MimeTypeDetector */
    private $mimeTypeDetector;
    /** @var VisibilityConverter */
    private $visibility;

    public function __construct(
        string $filename,
        string $root,
        ?MimeTypeDetector $mimeTypeDetector = null,
        ?VisibilityConverter $visibility = null
    ) {
        try {
            // create the root directory containing the zip archive
            new LocalFilesystemAdapter(dirname($filename));
        } catch (Throwable $exception) {
            throw UnableToOpenZipArchive::failedToCreateParentDirectory($filename, $exception);
        }

        $this->filename = $filename;
        $this->pathNormalizer = new ZipArchivePathNormalizer($root);
        $this->mimeTypeDetector = $mimeTypeDetector ?? new FinfoMimeTypeDetector();
        $this->visibility = $visibility ?? new PortableVisibilityConverter();
    }

    public function __destruct()
    {
        $this->close();
    }

    public function fileExists(string $path): bool
    {
        return $this->archive()->locateName($this->pathNormalizer->forFile($path)) !== false;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->ensureParentDirectoryExists($path);
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($path, 'creating parent directory failed', $exception);
        }

        if ( ! $this->archive()->addFromString($this->pathNormalizer->forFile($path), $contents)) {
            throw UnableToWriteFile::atLocation($path, 'writing the file failed');
        }

        $visibility = $config->get(Config::OPTION_VISIBILITY);
        if ($visibility === null) {
            return;
        }

        try {
            $this->setVisibility($path, $visibility);
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($path, 'setting visibility failed', $exception);
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
        $contents = $this->archive()->getFromName($this->pathNormalizer->forFile($path));

        if ($contents === false) {
            throw UnableToReadFile::fromLocation($path, $this->archive()->getStatusString());
        }

        return $contents;
    }

    public function readStream(string $path)
    {
        $resource = $this->archive()->getStream($this->pathNormalizer->forFile($path));

        if ($resource === false) {
            throw UnableToReadFile::fromLocation($path, $this->archive()->getStatusString());
        }

        return $resource;
    }

    public function delete(string $path): void
    {
        if ( ! $this->fileExists($path)) {
            return;
        }

        if ($this->archive()->deleteName($this->pathNormalizer->forFile($path))) {
            return;
        }

        throw UnableToDeleteFile::atLocation($path, $this->archive()->getStatusString());
    }

    public function deleteDirectory(string $path): void
    {
        $archive = $this->archive();
        $location = $this->pathNormalizer->forDirectory($path);

        for ($i = $archive->numFiles; $i > 0; $i--) {
            $stats = $archive->statIndex($i);
            if ($stats === false) {
                continue;
            }

            $path = $stats['name'];

            if ($location !== '' && strpos($path, $location) !== 0) {
                continue;
            }

            if ($archive->deleteIndex($i)) {
                continue;
            }

            throw UnableToDeleteDirectory::atLocation($path, $archive->getStatusString());
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        $this->ensureDirectoryExists($path);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $archive = $this->archive();
        $location = $this->pathNormalizer->forFile($path);
        $stats = $archive->statName($location);
        if ($stats === false) {
            throw UnableToSetVisibility::atLocation($path, $archive->getStatusString());
        }

        if (
            $archive->setExternalAttributesName(
                $location,
                ZipArchive::OPSYS_UNIX,
                (
                    $this->isDirectory($stats['name'])
                        ? $this->visibility->forDirectory($visibility)
                        : $this->visibility->forFile($visibility)
                ) << 16
            )
        ) {
            return;
        }

        throw UnableToSetVisibility::atLocation($path, $archive->getStatusString());
    }

    public function visibility(string $path): FileAttributes
    {
        $opsys = null;
        $attr = null;
        $this->archive()->getExternalAttributesName(
            $this->pathNormalizer->forFile($path),
            $opsys,
            $attr
        );

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
            $contents = $this->read($path);
            $mimetype = $this->mimeTypeDetector->detectMimeType($path, $contents);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::mimeType($path, '', $exception);
        }

        if ($mimetype === null) {
            throw UnableToRetrieveMetadata::mimeType($path, 'Unknown.');
        }

        return new FileAttributes($path, null, null, null, $mimetype);
    }

    public function lastModified(string $path): FileAttributes
    {
        $stats = $this->archive()->statName($this->pathNormalizer->forFile($path));

        if ($stats === false) {
            throw UnableToRetrieveMetadata::lastModified($path, $this->archive()->getStatusString());
        }

        return new FileAttributes($path, null, null, $stats['mtime']);
    }

    public function fileSize(string $path): FileAttributes
    {
        $stats = $this->archive()->statName($this->pathNormalizer->forFile($path));

        if ($stats === false) {
            throw UnableToRetrieveMetadata::fileSize($path, $this->archive()->getStatusString());
        }

        if ($this->isDirectory($stats['name'])) {
            throw UnableToRetrieveMetadata::fileSize($path, "It's a directory.");
        }

        return new FileAttributes($path, $stats['size'], null, null);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $archive = $this->archive();
        $location = $this->pathNormalizer->forDirectory($path);

        for ($i = 0; $i < $archive->numFiles; $i++) {
            $stats = $archive->statIndex($i);
            if ($stats === false) {
                continue;
            }

            $path = $stats['name'];

            if (
                $location === $path
                || ($location !== '' && strpos($path, $location) !== 0)
                || ( ! $deep && ! $this->isAtRootDirectory($location, $path))
            ) {
                continue;
            }

            yield $this->isDirectory($path)
                ? new DirectoryAttributes(
                    $this->pathNormalizer->inverseForDirectory($path),
                    null,
                    $stats['mtime']
                )
                : new FileAttributes(
                    $this->pathNormalizer->inverseForFile($path),
                    $stats['size'],
                    null,
                    $stats['mtime']
                );
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->ensureParentDirectoryExists($destination);
        } catch (Throwable $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }

        if (
            $this->archive()->renameName(
                $this->pathNormalizer->forFile($source),
                $this->pathNormalizer->forFile($destination)
            )
        ) {
            return;
        }

        throw UnableToMoveFile::fromLocationTo($source, $destination);
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

    private function archive(): ZipArchive
    {
        // ensure everything is flushed to disk
        $this->close();

        $this->archive = new ZipArchive();

        $ret = $this->archive->open($this->filename, ZipArchive::CREATE);
        if ($ret !== true) {
            throw UnableToOpenZipArchive::atLocation($this->filename, $this->archive->getStatusString());
        }

        return $this->archive;
    }

    private function close(): void
    {
        if ($this->archive === null) {
            return;
        }

        $this->archive->close();
        $this->archive = null;
    }

    private function ensureParentDirectoryExists(string $path): void
    {
        $dirname = dirname($path);

        if ($dirname === '' || $dirname === '.') {
            return;
        }

        $this->ensureDirectoryExists($dirname);
    }

    private function ensureDirectoryExists(string $dirname): void
    {
        $archive = $this->archive();

        $dirPath = '';
        $parts = explode('/', trim($dirname, '/'));

        foreach ($parts as $part) {
            $dirPath .= '/' . $part;
            $location = $this->pathNormalizer->forDirectory($dirPath);

            if ($archive->addEmptyDir($location)) {
                continue;
            }

            if ($archive->status === ZipArchive::ER_OK || $archive->status === ZipArchive::ER_EXISTS) {
                continue;
            }

            throw UnableToCreateDirectory::atLocation($dirname, $archive->getStatusString());
        }
    }

    private function isDirectory(string $path): bool
    {
        return substr($path, -1) === '/';
    }

    private function isAtRootDirectory(string $directory, string $path): bool
    {
        $parent = dirname($path) . '/';
        if ($directory === '' && $parent === './') {
            return true;
        }

        return $directory === $parent;
    }
}
