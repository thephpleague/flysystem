<?php

declare(strict_types=1);

namespace League\Flysystem\AwsS3V3;

use Aws\Api\DateTimeResult;
use Aws\S3\S3ClientInterface;
use DateTimeInterface;
use Generator;
use League\Flysystem\ChecksumAlgoIsNotSupported;
use League\Flysystem\ChecksumProvider;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperationFailed;
use League\Flysystem\PathPrefixer;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToGeneratePublicUrl;
use League\Flysystem\UnableToGenerateTemporaryUrl;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToProvideChecksum;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use League\Flysystem\Visibility;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use Psr\Http\Message\StreamInterface;
use Throwable;
use function trim;

class AwsS3V3Adapter implements FilesystemAdapter, PublicUrlGenerator, ChecksumProvider, TemporaryUrlGenerator
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
        'ChecksumAlgorithm',
        'CopySourceSSECustomerAlgorithm',
        'CopySourceSSECustomerKey',
        'CopySourceSSECustomerKeyMD5',
    ];
    /**
     * @var string[]
     */
    public const MUP_AVAILABLE_OPTIONS = [
        'add_content_md5',
        'before_upload',
        'concurrency',
        'mup_threshold',
        'params',
        'part_size',
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

    private PathPrefixer $prefixer;
    private VisibilityConverter $visibility;
    private MimeTypeDetector $mimeTypeDetector;

    public function __construct(
        private S3ClientInterface $client,
        private string $bucket,
        string $prefix = '',
        ?VisibilityConverter $visibility = null,
        ?MimeTypeDetector $mimeTypeDetector = null,
        private array $options = [],
        private bool $streamReads = true,
        private array $forwardedOptions = self::AVAILABLE_OPTIONS,
        private array $metadataFields = self::EXTRA_METADATA_FIELDS,
        private array $multipartUploadOptions = self::MUP_AVAILABLE_OPTIONS,
    ) {
        $this->prefixer = new PathPrefixer($prefix);
        $this->visibility = $visibility ?? new PortableVisibilityConverter();
        $this->mimeTypeDetector = $mimeTypeDetector ?? new FinfoMimeTypeDetector();
    }

    public function fileExists(string $path): bool
    {
        try {
            return $this->client->doesObjectExistV2($this->bucket, $this->prefixer->prefixPath($path), false, $this->options);
        } catch (Throwable $exception) {
            throw UnableToCheckFileExistence::forLocation($path, $exception);
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            $prefix = $this->prefixer->prefixDirectoryPath($path);
            $options = ['Bucket' => $this->bucket, 'Prefix' => $prefix, 'MaxKeys' => 1, 'Delimiter' => '/'];
            $command = $this->client->getCommand('ListObjectsV2', $options);
            $result = $this->client->execute($command);

            return $result->hasKey('Contents') || $result->hasKey('CommonPrefixes');
        } catch (Throwable $exception) {
            throw UnableToCheckDirectoryExistence::forLocation($path, $exception);
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
        $acl = $options['params']['ACL'] ?? $this->determineAcl($config);
        $shouldDetermineMimetype = ! array_key_exists('ContentType', $options['params']);

        if ($shouldDetermineMimetype && $mimeType = $this->mimeTypeDetector->detectMimeType($key, $body)) {
            $options['params']['ContentType'] = $mimeType;
        }

        try {
            $this->client->upload($this->bucket, $key, $body, $acl, $options);
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    private function determineAcl(Config $config): string
    {
        $visibility = (string) $config->get(Config::OPTION_VISIBILITY, Visibility::PRIVATE);

        return $this->visibility->visibilityToAcl($visibility);
    }

    private function createOptionsFromConfig(Config $config): array
    {
        $config = $config->withDefaults($this->options);
        $options = ['params' => []];

        if ($mimetype = $config->get('mimetype')) {
            $options['params']['ContentType'] = $mimetype;
        }

        foreach ($this->forwardedOptions as $option) {
            $value = $config->get($option, '__NOT_SET__');

            if ($value !== '__NOT_SET__') {
                $options['params'][$option] = $value;
            }
        }

        foreach ($this->multipartUploadOptions as $option) {
            $value = $config->get($option, '__NOT_SET__');

            if ($value !== '__NOT_SET__') {
                $options[$option] = $value;
            }
        }

        return $options;
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

        try {
            $this->client->deleteMatchingObjects($this->bucket, $prefix);
        } catch (Throwable $exception) {
            throw UnableToDeleteDirectory::atLocation($path, '', $exception);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        $defaultVisibility = $config->get(Config::OPTION_DIRECTORY_VISIBILITY, $this->visibility->defaultForDirectories());
        $config = $config->withDefaults([Config::OPTION_VISIBILITY => $defaultVisibility]);
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

    private function mapS3ObjectMetadata(array $metadata, string $path): StorageAttributes
    {
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

        foreach ($this->metadataFields as $field) {
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
        $prefix = $prefix === '' ? '' : $prefix . '/';
        $options = ['Bucket' => $this->bucket, 'Prefix' => $prefix];

        if ($deep === false) {
            $options['Delimiter'] = '/';
        }

        $listing = $this->retrievePaginatedListing($options);

        foreach ($listing as $item) {
            $key = $item['Key'] ?? $item['Prefix'];

            if ($key === $prefix) {
                continue;
            }

            yield $this->mapS3ObjectMetadata($item, $this->prefixer->stripPrefix($key));
        }
    }

    private function retrievePaginatedListing(array $options): Generator
    {
        $resultPaginator = $this->client->getPaginator('ListObjectsV2', $options + $this->options);

        foreach ($resultPaginator as $result) {
            yield from ($result->get('CommonPrefixes') ?? []);
            yield from ($result->get('Contents') ?? []);
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        if ($source === $destination) {
            return;
        }

        try {
            $this->copy($source, $destination, $config);
            $this->delete($source);
        } catch (FilesystemOperationFailed $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        if ($source === $destination) {
            return;
        }

        try {
            $visibility = $config->get(Config::OPTION_VISIBILITY);

            if ($visibility === null && $config->get(Config::OPTION_RETAIN_VISIBILITY, true)) {
                $visibility = $this->visibility($source)->visibility();
            }
        } catch (Throwable $exception) {
            throw UnableToCopyFile::fromLocationTo(
                $source,
                $destination,
                $exception
            );
        }

        $options = $this->createOptionsFromConfig($config);
        $options['MetadataDirective'] = $config->get('MetadataDirective', 'COPY');

        try {
            $this->client->copy(
                $this->bucket,
                $this->prefixer->prefixPath($source),
                $this->bucket,
                $this->prefixer->prefixPath($destination),
                $this->visibility->visibilityToAcl($visibility ?: 'private'),
                $options,
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

    public function publicUrl(string $path, Config $config): string
    {
        $location = $this->prefixer->prefixPath($path);

        try {
            return $this->client->getObjectUrl($this->bucket, $location);
        } catch (Throwable $exception) {
            throw UnableToGeneratePublicUrl::dueToError($path, $exception);
        }
    }

    public function checksum(string $path, Config $config): string
    {
        $algo = $config->get('checksum_algo', 'etag');

        if ($algo !== 'etag') {
            throw new ChecksumAlgoIsNotSupported();
        }

        try {
            $metadata = $this->fetchFileMetadata($path, 'checksum')->extraMetadata();
        } catch (UnableToRetrieveMetadata $exception) {
            throw new UnableToProvideChecksum($exception->reason(), $path, $exception);
        }

        if ( ! isset($metadata['ETag'])) {
            throw new UnableToProvideChecksum('ETag header not available.', $path);
        }

        return trim($metadata['ETag'], '"');
    }

    public function temporaryUrl(string $path, DateTimeInterface $expiresAt, Config $config): string
    {
        try {
            $options = $config->get('get_object_options', []);
            $command = $this->client->getCommand('GetObject', [
                    'Bucket' => $this->bucket,
                    'Key' => $this->prefixer->prefixPath($path),
                ] + $options);

            $presignedRequestOptions = $config->get('presigned_request_options', []);
            $request = $this->client->createPresignedRequest($command, $expiresAt, $presignedRequestOptions);

            return (string) $request->getUri();
        } catch (Throwable $exception) {
            throw UnableToGenerateTemporaryUrl::dueToError($path, $exception);
        }
    }
}
