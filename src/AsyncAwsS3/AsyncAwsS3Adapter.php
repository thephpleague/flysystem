<?php

declare(strict_types=1);

namespace League\Flysystem\AsyncAwsS3;

use AsyncAws\Core\Exception\Http\ClientException;
use AsyncAws\Core\Stream\ResultStream;
use AsyncAws\S3\Result\HeadObjectOutput;
use AsyncAws\S3\S3Client;
use AsyncAws\S3\ValueObject\AwsObject;
use AsyncAws\S3\ValueObject\CommonPrefix;
use AsyncAws\S3\ValueObject\ObjectIdentifier;
use AsyncAws\SimpleS3\SimpleS3Client;
use DateTimeImmutable;
use DateTimeInterface;
use Generator;
use League\Flysystem\ChecksumAlgoIsNotSupported;
use League\Flysystem\ChecksumProvider;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToGeneratePublicUrl;
use League\Flysystem\UnableToGenerateTemporaryUrl;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToProvideChecksum;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use League\Flysystem\Visibility;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use Throwable;
use function trim;

class AsyncAwsS3Adapter implements FilesystemAdapter, PublicUrlGenerator, ChecksumProvider, TemporaryUrlGenerator
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
        'ContentMD5',
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
    ];

    /**
     * @var string[]
     */
    protected const EXTRA_METADATA_FIELDS = [
        'Metadata',
        'StorageClass',
        'ETag',
        'VersionId',
    ];

    private PathPrefixer $prefixer;
    private VisibilityConverter $visibility;
    private MimeTypeDetector $mimeTypeDetector;

    /**
     * @var array|string[]
     */
    private array $forwardedOptions;

    /**
     * @var array|string[]
     */
    private array $metadataFields;

    /**
     * @param S3Client|SimpleS3Client $client Uploading of files larger than 5GB is only supported with SimpleS3Client
     */
    public function __construct(
        private S3Client $client,
        private string $bucket,
        string $prefix = '',
        VisibilityConverter $visibility = null,
        MimeTypeDetector $mimeTypeDetector = null,
        array $forwardedOptions = self::AVAILABLE_OPTIONS,
        array $metadataFields = self::EXTRA_METADATA_FIELDS,
    ) {
        $this->prefixer = new PathPrefixer($prefix);
        $this->visibility = $visibility ?: new PortableVisibilityConverter();
        $this->mimeTypeDetector = $mimeTypeDetector ?: new FinfoMimeTypeDetector();
        $this->forwardedOptions = $forwardedOptions;
        $this->metadataFields = $metadataFields;
    }

    public function fileExists(string $path): bool
    {
        try {
            return $this->client->objectExists(
                [
                    'Bucket' => $this->bucket,
                    'Key' => $this->prefixer->prefixPath($path),
                ]
            )->isSuccess();
        } catch (ClientException $e) {
            throw UnableToCheckFileExistence::forLocation($path, $e);
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->upload($path, $contents, $config);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->upload($path, $contents, $config);
    }

    public function read(string $path): string
    {
        $body = $this->readObject($path);

        return $body->getContentAsString();
    }

    public function readStream(string $path)
    {
        $body = $this->readObject($path);

        return $body->getContentAsResource();
    }

    public function delete(string $path): void
    {
        $arguments = ['Bucket' => $this->bucket, 'Key' => $this->prefixer->prefixPath($path)];

        try {
            $this->client->deleteObject($arguments);
        } catch (Throwable $exception) {
            throw UnableToDeleteFile::atLocation($path, '', $exception);
        }
    }

    public function deleteDirectory(string $path): void
    {
        $prefix = $this->prefixer->prefixDirectoryPath($path);
        $prefix = ltrim($prefix, '/');

        $objects = [];
        $params = ['Bucket' => $this->bucket, 'Prefix' => $prefix];
        $result = $this->client->listObjectsV2($params);
        /** @var AwsObject $item */
        foreach ($result->getContents() as $item) {
            $key = $item->getKey();
            if (null !== $key) {
                $objects[] = new ObjectIdentifier(['Key' => $key]);
            }
        }

        if (empty($objects)) {
            return;
        }

        foreach (array_chunk($objects, 1000) as $chunk) {
            $this->client->deleteObjects([
                'Bucket' => $this->bucket,
                'Delete' => ['Objects' => $chunk],
            ]);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        $defaultVisibility = $config->get('directory_visibility', $this->visibility->defaultForDirectories());
        $config = $config->withDefaults(['visibility' => $defaultVisibility]);
        $this->upload(rtrim($path, '/') . '/', '', $config);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $arguments = [
            'Bucket' => $this->bucket,
            'Key' => $this->prefixer->prefixPath($path),
            'ACL' => $this->visibility->visibilityToAcl($visibility),
        ];

        try {
            $this->client->putObjectAcl($arguments);
        } catch (Throwable $exception) {
            throw UnableToSetVisibility::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function visibility(string $path): FileAttributes
    {
        $arguments = ['Bucket' => $this->bucket, 'Key' => $this->prefixer->prefixPath($path)];

        try {
            $result = $this->client->getObjectAcl($arguments);
            $grants = $result->getGrants();
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::visibility($path, $exception->getMessage(), $exception);
        }

        $visibility = $this->visibility->aclToVisibility($grants);

        return new FileAttributes($path, null, $visibility);
    }

    public function mimeType(string $path): FileAttributes
    {
        $attributes = $this->fetchFileMetadata($path, FileAttributes::ATTRIBUTE_MIME_TYPE);

        if (null === $attributes->mimeType()) {
            throw UnableToRetrieveMetadata::mimeType($path);
        }

        return $attributes;
    }

    public function lastModified(string $path): FileAttributes
    {
        $attributes = $this->fetchFileMetadata($path, FileAttributes::ATTRIBUTE_LAST_MODIFIED);

        if (null === $attributes->lastModified()) {
            throw UnableToRetrieveMetadata::lastModified($path);
        }

        return $attributes;
    }

    public function fileSize(string $path): FileAttributes
    {
        $attributes = $this->fetchFileMetadata($path, FileAttributes::ATTRIBUTE_FILE_SIZE);

        if (null === $attributes->fileSize()) {
            throw UnableToRetrieveMetadata::fileSize($path);
        }

        return $attributes;
    }

    public function directoryExists(string $path): bool
    {
        try {
            $prefix = $this->prefixer->prefixDirectoryPath($path);
            $options = ['Bucket' => $this->bucket, 'Prefix' => $prefix, 'MaxKeys' => 1, 'Delimiter' => '/'];

            return $this->client->listObjectsV2($options)->getKeyCount() > 0;
        } catch (Throwable $exception) {
            throw UnableToCheckDirectoryExistence::forLocation($path, $exception);
        }
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $path = trim($path, '/');
        $prefix = trim($this->prefixer->prefixPath($path), '/');
        $prefix = empty($prefix) ? '' : $prefix . '/';
        $options = ['Bucket' => $this->bucket, 'Prefix' => $prefix];

        if (false === $deep) {
            $options['Delimiter'] = '/';
        }

        $listing = $this->retrievePaginatedListing($options);

        foreach ($listing as $item) {
            $item = $this->mapS3ObjectMetadata($item);

            if ($item->path() === $path) {
                continue;
            }

            yield $item;
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->copy($source, $destination, $config);
            $this->delete($source);
        } catch (Throwable $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            /** @var string $visibility */
            $visibility = $config->get(Config::OPTION_VISIBILITY) ?: $this->visibility($source)->visibility();
        } catch (Throwable $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }

        $arguments = [
            'ACL' => $this->visibility->visibilityToAcl($visibility),
            'Bucket' => $this->bucket,
            'Key' => $this->prefixer->prefixPath($destination),
            'CopySource' => $this->bucket . '/' . $this->prefixer->prefixPath($source),
        ];

        try {
            $this->client->copyObject($arguments);
        } catch (Throwable $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    /**
     * @param string|resource $body
     */
    private function upload(string $path, $body, Config $config): void
    {
        $key = $this->prefixer->prefixPath($path);
        $acl = $this->determineAcl($config);
        $options = $this->createOptionsFromConfig($config);
        $shouldDetermineMimetype = '' !== $body && ! \array_key_exists('ContentType', $options);

        if ($shouldDetermineMimetype && $mimeType = $this->mimeTypeDetector->detectMimeType($key, $body)) {
            $options['ContentType'] = $mimeType;
        }

        if ($this->client instanceof SimpleS3Client) {
            // Supports upload of files larger than 5GB
            $this->client->upload($this->bucket, $key, $body, array_merge($options, ['ACL' => $acl]));
        } else {
            $this->client->putObject(array_merge($options, [
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => $body,
                'ACL' => $acl,
            ]));
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

        foreach ($this->forwardedOptions as $option) {
            $value = $config->get($option, '__NOT_SET__');

            if ('__NOT_SET__' !== $value) {
                $options[$option] = $value;
            }
        }

        return $options;
    }

    private function fetchFileMetadata(string $path, string $type): FileAttributes
    {
        $arguments = ['Bucket' => $this->bucket, 'Key' => $this->prefixer->prefixPath($path)];

        try {
            $result = $this->client->headObject($arguments);
            $result->resolve();
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::create($path, $type, $exception->getMessage(), $exception);
        }

        $attributes = $this->mapS3ObjectMetadata($result, $path);

        if ( ! $attributes instanceof FileAttributes) {
            throw UnableToRetrieveMetadata::create($path, $type, 'Unable to retrieve file attributes, directory attributes received.');
        }

        return $attributes;
    }

    /**
     * @param HeadObjectOutput|AwsObject|CommonPrefix $item
     */
    private function mapS3ObjectMetadata($item, string $path = null): StorageAttributes
    {
        if (null === $path) {
            if ($item instanceof AwsObject) {
                $path = $this->prefixer->stripPrefix($item->getKey() ?? '');
            } elseif ($item instanceof CommonPrefix) {
                $path = $this->prefixer->stripPrefix($item->getPrefix() ?? '');
            } else {
                throw new \RuntimeException(sprintf('Argument 2 of "%s" cannot be null when $item is not instance of "%s" or %s', __METHOD__, AwsObject::class, CommonPrefix::class));
            }
        }

        if ('/' === substr($path, -1)) {
            return new DirectoryAttributes(rtrim($path, '/'));
        }

        $mimeType = null;
        $fileSize = null;
        $lastModified = null;
        $dateTime = null;
        $metadata = [];

        if ($item instanceof AwsObject) {
            $dateTime = $item->getLastModified();
            $fileSize = $item->getSize();
        } elseif ($item instanceof CommonPrefix) {
            // No data available
        } elseif ($item instanceof HeadObjectOutput) {
            $mimeType = $item->getContentType();
            $fileSize = $item->getContentLength();
            $dateTime = $item->getLastModified();
            $metadata = $this->extractExtraMetadata($item);
        } else {
            throw new \RuntimeException(sprintf('Object of class "%s" is not supported in %s()', \get_class($item), __METHOD__));
        }

        if ($dateTime instanceof \DateTimeInterface) {
            $lastModified = $dateTime->getTimestamp();
        }

        return new FileAttributes($path, $fileSize !== null ? (int) $fileSize : null, null, $lastModified, $mimeType, $metadata);
    }

    /**
     * @param HeadObjectOutput $metadata
     */
    private function extractExtraMetadata($metadata): array
    {
        $extracted = [];

        foreach ($this->metadataFields as $field) {
            $method = 'get' . $field;
            if ( ! method_exists($metadata, $method)) {
                continue;
            }
            $value = $metadata->$method();
            if (null !== $value) {
                $extracted[$field] = $value;
            }
        }

        return $extracted;
    }

    private function retrievePaginatedListing(array $options): Generator
    {
        $result = $this->client->listObjectsV2($options);

        foreach ($result as $item) {
            yield $item;
        }
    }

    private function readObject(string $path): ResultStream
    {
        $options = ['Bucket' => $this->bucket, 'Key' => $this->prefixer->prefixPath($path)];

        try {
            return $this->client->getObject($options)->getBody();
        } catch (Throwable $exception) {
            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function publicUrl(string $path, Config $config): string
    {
        if ( ! $this->client instanceof SimpleS3Client) {
            throw UnableToGeneratePublicUrl::noGeneratorConfigured($path, 'Client needs to be instance of SimpleS3Client');
        }

        try {
            return $this->client->getUrl($this->bucket, $this->prefixer->prefixPath($path));
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
        $location = $this->prefixer->prefixPath($path);

        try {
            return $this->client->getPresignedUrl($this->bucket, $location, DateTimeImmutable::createFromInterface($expiresAt));
        } catch (Throwable $exception) {
            throw UnableToGenerateTemporaryUrl::dueToError($path, $exception);
        }
    }
}
