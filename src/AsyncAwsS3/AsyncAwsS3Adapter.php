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
use Generator;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\EncryptedFilesystemAdapter;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\Visibility;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use Throwable;

class AsyncAwsS3Adapter implements FilesystemAdapter, EncryptedFilesystemAdapter
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
     * @var S3Client
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
     * @param S3Client|SimpleS3Client $client Uploading of files larger than 5GB is only supported with SimpleS3Client
     */
    public function __construct(
        S3Client $client,
        string $bucket,
        string $prefix = '',
        VisibilityConverter $visibility = null,
        MimeTypeDetector $mimeTypeDetector = null
    ) {
        $this->client = $client;
        $this->prefixer = new PathPrefixer($prefix);
        $this->bucket = $bucket;
        $this->visibility = $visibility ?: new PortableVisibilityConverter();
        $this->mimeTypeDetector = $mimeTypeDetector ?: new FinfoMimeTypeDetector();
    }

    public function fileExists(string $path): bool
    {
        return $this->fileExistsImp($path);
    }

    public function encryptedFileExists(string $path, string $encryptionKey): bool
    {
        return $this->fileExistsImp($path, $this->generateEncryptionConfig($encryptionKey));
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->upload($path, $contents, $config);
    }

    public function encryptedWrite(string $path, string $contents, Config $config, string $encryptionKey): void
    {
        $this->upload($path, $contents, $config, $this->generateEncryptionConfig($encryptionKey));
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->upload($path, $contents, $config);
    }

    public function encryptedWriteStream(string $path, $contents, Config $config, string $encryptionKey): void
    {
        $this->upload($path, $contents, $config, $this->generateEncryptionConfig($encryptionKey));
    }

    public function read(string $path): string
    {
        $body = $this->readObject($path);

        return $body->getContentAsString();
    }

    public function encryptedRead(string $path, string $encryptionKey): string
    {
        $body = $this->readObject($path, $this->generateEncryptionConfig($encryptionKey));

        return $body->getContentAsString();
    }

    public function readStream(string $path)
    {
        $body = $this->readObject($path);

        return $body->getContentAsResource();
    }

    public function encryptedReadStream(string $path, string $encryptionKey)
    {
        $body = $this->readObject($path, $this->generateEncryptionConfig($encryptionKey));

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
        $prefix = $this->prefixer->prefixPath($path);
        $prefix = ltrim(rtrim($prefix, '/') . '/', '/');

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

        $this->client->deleteObjects(['Bucket' => $this->bucket, 'Delete' => ['Objects' => $objects]]);
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

        try {
            $this->client->putObjectAcl($arguments);
        } catch (Throwable $exception) {
            throw UnableToSetVisibility::atLocation($path, '', $exception);
        }
    }

    public function visibility(string $path): FileAttributes
    {
        $arguments = ['Bucket' => $this->bucket, 'Key' => $this->prefixer->prefixPath($path)];

        try {
            $result = $this->client->getObjectAcl($arguments);
            $grants = $result->getGrants();
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::visibility($path, '', $exception);
        }

        $visibility = $this->visibility->aclToVisibility($grants);

        return new FileAttributes($path, null, $visibility);
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->mimeTypeImpl($path);
    }

    public function encryptedMimeType(string $path, string $encryptionKey): FileAttributes
    {
        return $this->mimeTypeImpl($path, $this->generateEncryptionConfig($encryptionKey));
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->lastModifiedImpl($path);
    }

    public function encryptedLastModified(string $path, string $encryptionKey): FileAttributes
    {
        return $this->lastModifiedImpl($path, $this->generateEncryptionConfig($encryptionKey));
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->fileSizeImpl($path);
    }

    public function encryptedFileSize(string $path, string $encryptionKey): FileAttributes
    {
        return $this->fileSizeImpl($path, $this->generateEncryptionConfig($encryptionKey));
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $prefix = trim($this->prefixer->prefixPath($path), '/');
        $prefix = empty($prefix) ? '' : $prefix . '/';
        $options = ['Bucket' => $this->bucket, 'Prefix' => $prefix];

        if (false === $deep) {
            $options['Delimiter'] = '/';
        }

        $listing = $this->retrievePaginatedListing($options);

        foreach ($listing as $item) {
            yield $this->mapS3ObjectMetadata($item);
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

    public function encryptedMove(string $source, string $destination, Config $config, string $sourceKey, ?string $destinationKey = null): void
    {
        try {
            $this->copyImpl($source, $destination, $config, $sourceKey, $destinationKey);
            $this->delete($source);
        } catch (Throwable $exception) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $exception);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->copyImpl($source, $destination, $config);
    }

    public function encryptedCopy(string $source, string $destination, Config $config, string $sourceKey, ?string $destinationKey = null): void
    {
        $this->copyImpl($source, $destination, $config, $sourceKey, $destinationKey);
    }

    /**
     * @param string|resource $body
     */
    private function upload(string $path, $body, Config $config, array $extraConfig = []): void
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
            $this->client->upload($this->bucket, $key, $body, array_merge($options, ['ACL' => $acl], $extraConfig));
        } else {
            $this->client->putObject(array_merge($options, [
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => $body,
                'ACL' => $acl,
            ],
            $extraConfig
            ));
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

        foreach (static::AVAILABLE_OPTIONS as $option) {
            $value = $config->get($option, '__NOT_SET__');

            if ('__NOT_SET__' !== $value) {
                $options[$option] = $value;
            }
        }

        return $options;
    }

    private function fetchFileMetadata(string $path, string $type, array $config = []): FileAttributes
    {
        $arguments = ['Bucket' => $this->bucket, 'Key' => $this->prefixer->prefixPath($path)];

        try {
            $result = $this->client->headObject(array_merge($arguments, $config));
            $result->resolve();
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::create($path, $type, '', $exception);
        }

        $attributes = $this->mapS3ObjectMetadata($result, $path);

        if ( ! $attributes instanceof FileAttributes) {
            throw UnableToRetrieveMetadata::create($path, $type, '');
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

        foreach (static::EXTRA_METADATA_FIELDS as $field) {
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

    private function readObject(string $path, array $config = []): ResultStream
    {
        $options = ['Bucket' => $this->bucket, 'Key' => $this->prefixer->prefixPath($path)];

        try {
            return $this->client->getObject(array_merge($options, $config))->getBody();
        } catch (Throwable $exception) {
            throw UnableToReadFile::fromLocation($path, '', $exception);
        }
    }

    private function fileExistsImp(string $path, array $config = []): bool
    {
        try {
            return $this->client->objectExists(
                array_merge(
                    [
                        'Bucket' => $this->bucket,
                        'Key' => $this->prefixer->prefixPath($path),
                    ],
                    $config
                )
            )->isSuccess();
        } catch (ClientException $e) {
            throw UnableToCheckFileExistence::forLocation($path, $e);
        }
    }

    private function mimeTypeImpl(string $path, array $config = []): FileAttributes
    {
        $attributes = $this->fetchFileMetadata($path, FileAttributes::ATTRIBUTE_MIME_TYPE, $config);

        if (null === $attributes->mimeType()) {
            throw UnableToRetrieveMetadata::mimeType($path);
        }

        return $attributes;
    }

    private function lastModifiedImpl(string $path, array $config = []): FileAttributes
    {
        $attributes = $this->fetchFileMetadata($path, FileAttributes::ATTRIBUTE_LAST_MODIFIED, $config);

        if (null === $attributes->lastModified()) {
            throw UnableToRetrieveMetadata::lastModified($path);
        }

        return $attributes;
    }

    private function fileSizeImpl(string $path, array $config = []): FileAttributes
    {
        $attributes = $this->fetchFileMetadata($path, FileAttributes::ATTRIBUTE_FILE_SIZE, $config);

        if (null === $attributes->fileSize()) {
            throw UnableToRetrieveMetadata::fileSize($path);
        }

        return $attributes;
    }

    private function copyImpl(string $source, string $destination, Config $config, ?string $sourceKey = null, ?string $destinationKey = null): void
    {
        try {
            /** @var string $visibility */
            $visibility = $this->visibility($source)->visibility();
        } catch (Throwable $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }

        $arguments = [
            'ACL' => $this->visibility->visibilityToAcl($visibility),
            'Bucket' => $this->bucket,
            'Key' => $this->prefixer->prefixPath($destination),
            'CopySource' => $this->bucket . '/' . $this->prefixer->prefixPath($source),
        ];

        if ($sourceKey) {
            $arguments = array_merge($arguments,[
                'CopySourceSSECustomerAlgorithm' => 'AES256',
                'CopySourceSSECustomerKey' => base64_encode($sourceKey),
                'CopySourceSSECustomerKeyMD5' => base64_encode(md5($sourceKey)),
            ]);
        }

        if ($destinationKey) {
            $arguments = array_merge($arguments,$this->generateEncryptionConfig($destinationKey));
        }

        try {
            $this->client->copyObject($arguments);
        } catch (Throwable $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    private function generateEncryptionConfig(string $encryptionKey): array
    {
        return [
            'SSECustomerAlgorithm' => 'AES256',
            'SSECustomerKey' => base64_encode($encryptionKey),
            'SSECustomerKeyMD5' => base64_encode(md5($encryptionKey))
        ];
    }
}
