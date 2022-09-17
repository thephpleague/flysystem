<?php

declare(strict_types=1);

namespace League\Flysystem\AwsS3V3;

use Aws\Api\DateTimeResult;
use Aws\S3\S3ClientInterface;
use Generator;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperationFailed;
use League\Flysystem\PathPrefixer;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use Psr\Http\Message\StreamInterface;
use Throwable;

class AwsS3V3Adapter implements FilesystemAdapter
{
    /**
     * @var string[]
     */
    public const AVAILABLE_OPTIONS = [
        'ACL',
        'CacheControl',
        'ContentDisposition',
        'ContentEncoding',
        'ContentLength',
        'ContentType',
        'Expires',
        'GrantFullControl',
        'GrantRead',
        'GrantReadACP',
        'GrantWriteACP',
        'Metadata',
        'MetadataDirective',
        'RequestPayer',
        'SSECustomerAlgorithm',
        'SSECustomerKey',
        'SSECustomerKeyMD5',
        'SSEKMSKeyId',
        'ServerSideEncryption',
        'StorageClass',
        'Tagging',
        'WebsiteRedirectLocation',
    ];

    /**
     * @var string[]
     */
    private const EXTRA_METADATA_FIELDS = [
        'Metadata',
        'StorageClass',
        'ETag',
        'VersionId',
    ];

    /**
     * @var S3ClientInterface
     */
    private $client;

    /**
     * @var PathPrefixer
     */
    private $prefixer;

    /**
     * @var string
     */
    private $bucket;

    /**
     * @var VisibilityConverter
     */
    private $visibility;

    /**
     * @var MimeTypeDetector
     */
    private $mimeTypeDetector;

    /**
     * @var array
     */
    private $options;

    /**
     * @var bool
     */
    private $streamReads;

    public function __construct(
        S3ClientInterface $client,
        string $bucket,
        string $prefix = '',
        VisibilityConverter $visibility = null,
        MimeTypeDetector $mimeTypeDetector = null,
        array $options = [],
        bool $streamReads = true
    ) {
        $this->client = $client;
        $this->prefixer = new PathPrefixer($prefix);
        $this->bucket = $bucket;
        $this->visibility = $visibility ?: new PortableVisibilityConverter();
        $this->mimeTypeDetector = $mimeTypeDetector ?: new FinfoMimeTypeDetector();
        $this->options = $options;
        $this->streamReads = $streamReads;
    }

    public function fileExists(string $path): bool
    {
        try {
            return $this->client->doesObjectExist($this->bucket, $this->prefixer->prefixPath($path), $this->options);
        } catch (Throwable $exception) {
            throw UnableToCheckFileExistence::forLocation($path, $exception);
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->upload($path, $contents, $config);
    }

    /**
     * @param string          $path
     * @param string|resource $body
     * @param Config          $config
     */
    private function upload(string $path, $body, Config $config): void
    {
        $key = $this->prefixer->prefixPath($path);
        $options = $this->createOptionsFromConfig($config);
        $acl = $options['ACL'] ?? $this->determineAcl($config);
        $shouldDetermineMimetype = $body !== '' && ! array_key_exists('ContentType', $options);

        if ($shouldDetermineMimetype && $mimeType = $this->mimeTypeDetector->detectMimeType($key, $body)) {
            $options['ContentType'] = $mimeType;
        }

        try {
            $this->client->upload($this->bucket, $key, $body, $acl, ['params' => $options]);
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($path, '', $exception);
        }
    }

    private function determineAcl(Config $config): string
    {
        $visibility = (string) $config->get(Config::OPTION_VISIBILITY, Visibility::PRIVATE);

        return $this->visibility->visibilityToAcl($visibility);
    }

    private function createOptionsFromConfig(Config $config): array
    {
        $options = [];

        if ($mimetype = $config->get('mimetype')) {
            $options['ContentType'] = $mimetype;
        }

        foreach (static::AVAILABLE_OPTIONS as $option) {
            $value = $config->get($option, '__NOT_SET__');

            if ($value !== '__NOT_SET__') {
                $options[$option] = $value;
            }
        }

        return $options + $this->options;
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->upload($path, $contents, $config);
    }

    public function read(string $path): string
    {
        $body = $this->readObject($path, false);

        return (string) $body->getContents();
    }

    public function readStream(string $path)
    {
        /** @var resource $resource */
        $resource = $this->readObject($path, true)->detach();

        return $resource;
    }

    public function delete(string $path): void
    {
        $arguments = ['Bucket' => $this->bucket, 'Key' => $this->prefixer->prefixPath($path)];
        $command = $this->client->getCommand('DeleteObject', $arguments);

        try {
            $this->client->execute($command);
        } catch (Throwable $exception) {
            throw UnableToDeleteFile::atLocation($path, '', $exception);
        }
    }

    public function deleteDirectory(string $path): void
    {
        $prefix = $this->prefixer->prefixPath($path);
        $prefix = ltrim(rtrim($prefix, '/') . '/', '/');
        $this->client->deleteMatchingObjects($this->bucket, $prefix);
    }

    public function createDirectory(string $path, Config $config): void
    {
        $config = $config->withDefaults(['visibility' => $this->visibility->defaultForDirectories()]);
        $this->upload(rtrim($path, '/') . '/', '', $config);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $arguments = [
            'Bucket' => $this->bucket,
            'Key' => $this->prefixer->prefixPath($path),
            'ACL' => $this->visibility->visibilityToAcl($visibility),
        ];
        $command = $this->client->getCommand('PutObjectAcl', $arguments);

        try {
            $this->client->execute($command);
        } catch (Throwable $exception) {
            throw UnableToSetVisibility::atLocation($path, '', $exception);
        }
    }

    public function visibility(string $path): FileAttributes
    {
        $arguments = ['Bucket' => $this->bucket, 'Key' => $this->prefixer->prefixPath($path)];
        $command = $this->client->getCommand('GetObjectAcl', $arguments);

        try {
            $result = $this->client->execute($command);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::visibility($path, '', $exception);
        }

        $visibility = $this->visibility->aclToVisibility((array) $result->get('Grants'));

        return new FileAttributes($path, null, $visibility);
    }

    private function fetchFileMetadata(string $path, string $type): FileAttributes
    {
        $arguments = ['Bucket' => $this->bucket, 'Key' => $this->prefixer->prefixPath($path)];
        $command = $this->client->getCommand('HeadObject', $arguments);

        try {
            $result = $this->client->execute($command);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::create($path, $type, '', $exception);
        }

        $attributes = $this->mapS3ObjectMetadata($result->toArray(), $path);

        if ( ! $attributes instanceof FileAttributes) {
            throw UnableToRetrieveMetadata::create($path, $type, '');
        }

        return $attributes;
    }

    private function mapS3ObjectMetadata(array $metadata, string $path = null): StorageAttributes
    {
        if ($path === null) {
            $path = $this->prefixer->stripPrefix($metadata['Key'] ?? $metadata['Prefix']);
        }

        if (substr($path, -1) === '/') {
            return new DirectoryAttributes(rtrim($path, '/'));
        }

        $mimetype = $metadata['ContentType'] ?? null;
        $fileSize = $metadata['ContentLength'] ?? $metadata['Size'] ?? null;
        $fileSize = $fileSize === null ? null : (int) $fileSize;
        $dateTime = $metadata['LastModified'] ?? null;
        $lastModified = $dateTime instanceof DateTimeResult ? $dateTime->getTimeStamp() : null;

        return new FileAttributes(
            $path,
            $fileSize,
            null,
            $lastModified,
            $mimetype,
            $this->extractExtraMetadata($metadata)
        );
    }

    private function extractExtraMetadata(array $metadata): array
    {
        $extracted = [];

        foreach (static::EXTRA_METADATA_FIELDS as $field) {
            if (isset($metadata[$field]) && $metadata[$field] !== '') {
                $extracted[$field] = $metadata[$field];
            }
        }

        return $extracted;
    }

    public function mimeType(string $path): FileAttributes
    {
        $attributes = $this->fetchFileMetadata($path, FileAttributes::ATTRIBUTE_MIME_TYPE);

        if ($attributes->mimeType() === null) {
            throw UnableToRetrieveMetadata::mimeType($path);
        }

        return $attributes;
    }

    public function lastModified(string $path): FileAttributes
    {
        $attributes = $this->fetchFileMetadata($path, FileAttributes::ATTRIBUTE_LAST_MODIFIED);

        if ($attributes->lastModified() === null) {
            throw UnableToRetrieveMetadata::lastModified($path);
        }

        return $attributes;
    }

    public function fileSize(string $path): FileAttributes
    {
        $attributes = $this->fetchFileMetadata($path, FileAttributes::ATTRIBUTE_FILE_SIZE);

        if ($attributes->fileSize() === null) {
            throw UnableToRetrieveMetadata::fileSize($path);
        }

        return $attributes;
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $prefix = trim($this->prefixer->prefixPath($path), '/');
        $prefix = empty($prefix) ? '' : $prefix . '/';
        $options = ['Bucket' => $this->bucket, 'Prefix' => $prefix];

        if ($deep === false) {
            $options['Delimiter'] = '/';
        }

        $listing = $this->retrievePaginatedListing($options);

        foreach ($listing as $item) {
            yield $this->mapS3ObjectMetadata($item);
        }
    }

    private function retrievePaginatedListing(array $options): Generator
    {
        $resultPaginator = $this->client->getPaginator('ListObjects', $options + $this->options);

        foreach ($resultPaginator as $result) {
            yield from ($result->get('CommonPrefixes') ?: []);
            yield from ($result->get('Contents') ?: []);
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->copy($source, $destination, $config);
            $this->delete($source);
        } catch (FilesystemOperationFailed $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            /** @var string $visibility */
            $visibility = $config->get(Config::OPTION_VISIBILITY) ?: $this->visibility($source)->visibility();
        } catch (Throwable $exception) {
            throw UnableToCopyFile::fromLocationTo(
                $source,
                $destination,
                $exception
            );
        }

        try {
            $this->client->copy(
                $this->bucket,
                $this->prefixer->prefixPath($source),
                $this->bucket,
                $this->prefixer->prefixPath($destination),
                $this->visibility->visibilityToAcl($visibility),
                $this->createOptionsFromConfig($config)
            );
        } catch (Throwable $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    private function readObject(string $path, bool $wantsStream): StreamInterface
    {
        $options = ['Bucket' => $this->bucket, 'Key' => $this->prefixer->prefixPath($path)];

        if ($wantsStream && $this->streamReads && ! isset($this->options['@http']['stream'])) {
            $options['@http']['stream'] = true;
        }

        $command = $this->client->getCommand('GetObject', $options + $this->options);

        try {
            return $this->client->execute($command)->get('Body');
        } catch (Throwable $exception) {
            throw UnableToReadFile::fromLocation($path, '', $exception);
        }
    }
}
