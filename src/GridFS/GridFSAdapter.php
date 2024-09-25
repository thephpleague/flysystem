<?php

declare(strict_types=1);

namespace League\Flysystem\GridFS;

use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Exception\Exception;
use MongoDB\GridFS\Bucket;
use MongoDB\GridFS\Exception\FileNotFoundException;

/**
 * @phpstan-type GridFile array{_id:ObjectId, length:int, chunkSize:int, uploadDate:UTCDateTime, filename:string, metadata?:array{contentType?:string, flysystem_visibility?:string}}
 */
class GridFSAdapter implements FilesystemAdapter
{
    private const METADATA_DIRECTORY = 'flysystem_directory';
    private const METADATA_VISIBILITY = 'flysystem_visibility';
    private const METADATA_MIMETYPE = 'contentType';
    private const TYPEMAP_ARRAY = [
        'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
        'codec' => null,
    ];

    private Bucket $bucket;

    private PathPrefixer $prefixer;

    private MimeTypeDetector $mimeTypeDetector;

    public function __construct(
        Bucket $bucket,
        string $prefix = '',
        ?MimeTypeDetector $mimeTypeDetector = null,
    ) {
        $this->bucket = $bucket;
        $this->prefixer = new PathPrefixer($prefix);
        $this->mimeTypeDetector = $mimeTypeDetector ?? new FinfoMimeTypeDetector();
    }

    public function fileExists(string $path): bool
    {
        $file = $this->findFile($path);

        return $file !== null;
    }

    public function directoryExists(string $path): bool
    {
        // A directory exists if at least one file exists with a path starting with the directory name
        $files = $this->listContents($path, true);

        foreach ($files as $file) {
            return true;
        }

        return false;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        if (str_ends_with($path, '/')) {
            throw UnableToWriteFile::atLocation($path, 'file path cannot end with a slash');
        }

        $filename = $this->prefixer->prefixPath($path);
        $options = [
            'metadata' => $config->get('metadata', []),
        ];
        if ($visibility = $config->get(Config::OPTION_VISIBILITY)) {
            $options['metadata'][self::METADATA_VISIBILITY] = $visibility;
        }
        if (($mimeType = $config->get('mimetype')) || ($mimeType = $this->mimeTypeDetector->detectMimeType($path, $contents))) {
            $options['metadata'][self::METADATA_MIMETYPE] = $mimeType;
        }

        try {
            $stream = $this->bucket->openUploadStream($filename, $options);
            fwrite($stream, $contents);
            fclose($stream);
        } catch (Exception $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        if (str_ends_with($path, '/')) {
            throw UnableToWriteFile::atLocation($path, 'file path cannot end with a slash');
        }

        $filename = $this->prefixer->prefixPath($path);
        $options = [];
        if ($visibility = $config->get(Config::OPTION_VISIBILITY)) {
            $options['metadata'][self::METADATA_VISIBILITY] = $visibility;
        }
        if (($mimetype = $config->get('mimetype')) || ($mimetype = $this->mimeTypeDetector->detectMimeTypeFromPath($path))) {
            $options['metadata'][self::METADATA_MIMETYPE] = $mimetype;
        }

        try {
            $this->bucket->uploadFromStream($filename, $contents, $options);
        } catch (Exception $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function read(string $path): string
    {
        $stream = $this->readStream($path);
        try {
            return stream_get_contents($stream);
        } finally {
            fclose($stream);
        }
    }

    public function readStream(string $path)
    {
        if (str_ends_with($path, '/')) {
            throw UnableToReadFile::fromLocation($path, 'file path cannot end with a slash');
        }

        try {
            $filename = $this->prefixer->prefixPath($path);

            return $this->bucket->openDownloadStreamByName($filename);
        } catch (FileNotFoundException $exception) {
            throw UnableToReadFile::fromLocation($path, 'file does not exist', $exception);
        } catch (Exception $exception) {
            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }
    }

    /**
     * Delete all revisions of the file name, starting with the oldest,
     * no-op if the file does not exist.
     *
     * @throws UnableToDeleteFile
     */
    public function delete(string $path): void
    {
        if (str_ends_with($path, '/')) {
            throw UnableToDeleteFile::atLocation($path, 'file path cannot end with a slash');
        }

        $filename = $this->prefixer->prefixPath($path);
        try {
            $this->findAndDelete(['filename' => $filename]);
        } catch (Exception $exception) {
            throw UnableToDeleteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function deleteDirectory(string $path): void
    {
        $prefixedPath = $this->prefixer->prefixDirectoryPath($path);
        try {
            $this->findAndDelete(['filename' => new Regex('^' . preg_quote($prefixedPath))]);
        } catch (Exception $exception) {
            throw UnableToDeleteDirectory::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        $dirname = $this->prefixer->prefixDirectoryPath($path);

        $options = [
            'metadata' => $config->get('metadata', []) + [self::METADATA_DIRECTORY => true],
        ];

        if ($visibility = $config->get(Config::OPTION_VISIBILITY)) {
            $options['metadata'][self::METADATA_VISIBILITY] = $visibility;
        }

        try {
            $stream = $this->bucket->openUploadStream($dirname, $options);
            fwrite($stream, '');
            fclose($stream);
        } catch (Exception $exception) {
            throw UnableToCreateDirectory::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $file = $this->findFile($path);

        if ($file === null) {
            throw UnableToSetVisibility::atLocation($path, 'file does not exist');
        }

        try {
            $this->bucket->getFilesCollection()->updateOne(
                ['_id' => $file['_id']],
                ['$set' => ['metadata.' . self::METADATA_VISIBILITY => $visibility]],
            );
        } catch (Exception $exception) {
            throw UnableToSetVisibility::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function visibility(string $path): FileAttributes
    {
        $file = $this->findFile($path);

        if ($file === null) {
            throw UnableToRetrieveMetadata::mimeType($path, 'file does not exist');
        }

        return $this->mapFileAttributes($file);
    }

    public function fileSize(string $path): FileAttributes
    {
        if (str_ends_with($path, '/')) {
            throw UnableToRetrieveMetadata::fileSize($path, 'file path cannot end with a slash');
        }

        $file = $this->findFile($path);
        if ($file === null) {
            throw UnableToRetrieveMetadata::fileSize($path, 'file does not exist');
        }

        return $this->mapFileAttributes($file);
    }

    public function mimeType(string $path): FileAttributes
    {
        if (str_ends_with($path, '/')) {
            throw UnableToRetrieveMetadata::mimeType($path, 'file path cannot end with a slash');
        }

        $file = $this->findFile($path);
        if ($file === null) {
            throw UnableToRetrieveMetadata::mimeType($path, 'file does not exist');
        }

        $attributes = $this->mapFileAttributes($file);
        if ($attributes->mimeType() === null) {
            throw UnableToRetrieveMetadata::mimeType($path, 'unknown');
        }

        return $attributes;
    }

    public function lastModified(string $path): FileAttributes
    {
        if (str_ends_with($path, '/')) {
            throw UnableToRetrieveMetadata::lastModified($path, 'file path cannot end with a slash');
        }

        $file = $this->findFile($path);
        if ($file === null) {
            throw UnableToRetrieveMetadata::lastModified($path, 'file does not exist');
        }

        return $this->mapFileAttributes($file);
    }

    public function metadata(string $path, Config $config): StorageAttributes
    {
        if ($this->directoryExists($path)) {
            return new DirectoryAttributes($path);
        }

        $file = $this->findFile($path);

        if ($file === null) {
            throw UnableToRetrieveMetadata::metadata($path, 'file does not exist');
        }

        return $this->mapFileAttributes($file);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $path = $this->prefixer->prefixDirectoryPath($path);

        $pathdeep = 0;
        // Get the last revision of each file, using the index on the files collection
        $pipeline = [['$sort' => ['filename' => 1, 'uploadDate' => 1]]];
        if ($path !== '') {
            $pathdeep = substr_count($path, '/');
            // Exclude files that do not start with the expected path
            $pipeline[] = ['$match' => ['filename' => new Regex('^' . preg_quote($path))]];
        }

        if ($deep === false) {
            $pipeline[] = ['$addFields' => ['splitpath' => ['$split' => ['$filename', '/']]]];
            $pipeline[] = ['$group' => [
                // The same name could be used as a filename and as part of the path of other files
                '_id' => [
                    'basename' => ['$arrayElemAt' => ['$splitpath', $pathdeep]],
                    'isDir' => ['$ne' => [['$size' => '$splitpath'], $pathdeep + 1]],
                ],
                // Get the metadata of the last revision of each file
                'file' => ['$last' => '$$ROOT'],
                // The "lastModified" date is the date of the last uploaded file in the directory
                'uploadDate' => ['$max' => '$uploadDate'],
            ]];

            $files = $this->bucket->getFilesCollection()->aggregate($pipeline, self::TYPEMAP_ARRAY);

            foreach ($files as $file) {
                if ($file['_id']['isDir']) {
                    yield new DirectoryAttributes(
                        $this->prefixer->stripDirectoryPrefix($path . $file['_id']['basename']),
                        null,
                        $file['uploadDate']->toDateTime()->getTimestamp(),
                    );
                } else {
                    yield $this->mapFileAttributes($file['file']);
                }
            }
        } else {
            // Get the metadata of the last revision of each file
            $pipeline[] = ['$group' => [
                '_id' => '$filename',
                'file' => ['$first' => '$$ROOT'],
            ]];

            $files = $this->bucket->getFilesCollection()->aggregate($pipeline, self::TYPEMAP_ARRAY);

            foreach ($files as $file) {
                $file = $file['file'];
                if (str_ends_with($file['filename'], '/')) {
                    // Empty files with a trailing slash are markers for directories, only for Flysystem
                    yield new DirectoryAttributes(
                        $this->prefixer->stripDirectoryPrefix($file['filename']),
                        $file['metadata'][self::METADATA_VISIBILITY] ?? null,
                        $file['uploadDate']->toDateTime()->getTimestamp(),
                        $file,
                    );
                } else {
                    yield $this->mapFileAttributes($file);
                }
            }
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        if ($source === $destination) {
            return;
        }

        if ($this->fileExists($destination)) {
            $this->delete($destination);
        }

        try {
            $result = $this->bucket->getFilesCollection()->updateMany(
                ['filename' => $this->prefixer->prefixPath($source)],
                ['$set' => ['filename' => $this->prefixer->prefixPath($destination)]],
            );

            if ($result->getModifiedCount() === 0) {
                throw UnableToMoveFile::because('file does not exist', $source, $destination);
            }
        } catch (Exception $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $file = $this->findFile($source);

        if ($file === null) {
            throw UnableToCopyFile::fromLocationTo(
                $source,
                $destination,
            );
        }

        $options = [];
        if (($visibility = $config->get(Config::OPTION_VISIBILITY)) || $visibility = $file['metadata'][self::METADATA_VISIBILITY] ?? null) {
            $options['metadata'][self::METADATA_VISIBILITY] = $visibility;
        }
        if (($mimetype = $config->get('mimetype')) || $mimetype = $file['metadata'][self::METADATA_MIMETYPE] ?? null) {
            $options['metadata'][self::METADATA_MIMETYPE] = $mimetype;
        }

        try {
            $stream = $this->bucket->openDownloadStream($file['_id']);
            $this->bucket->uploadFromStream($this->prefixer->prefixPath($destination), $stream, $options);
        } catch (Exception $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    /**
     * Get the last revision of the file name.
     *
     * @return GridFile|null
     */
    private function findFile(string $path): ?array
    {
        $filename = $this->prefixer->prefixPath($path);
        $files = $this->bucket->find(
            ['filename' => $filename],
            ['sort' => ['uploadDate' => -1], 'limit' => 1] + self::TYPEMAP_ARRAY,
        );

        return $files->toArray()[0] ?? null;
    }

    /**
     * @param GridFile $file
     */
    private function mapFileAttributes(array $file): FileAttributes
    {
        return new FileAttributes(
            $this->prefixer->stripPrefix($file['filename']),
            $file['length'],
            $file['metadata'][self::METADATA_VISIBILITY] ?? null,
            $file['uploadDate']->toDateTime()->getTimestamp(),
            $file['metadata'][self::METADATA_MIMETYPE] ?? null,
            $file,
        );
    }

    /**
     * @throws Exception
     */
    private function findAndDelete(array $filter): void
    {
        $files = $this->bucket->find(
            $filter,
            ['sort' => ['uploadDate' => 1], 'projection' => ['_id' => 1]] + self::TYPEMAP_ARRAY,
        );

        foreach ($files as $file) {
            try {
                $this->bucket->delete($file['_id']);
            } catch (FileNotFoundException) {
                // Ignore error due to race condition
            }
        }
    }
}
