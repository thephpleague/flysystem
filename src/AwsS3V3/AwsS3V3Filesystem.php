<?php

declare(strict_types=1);

namespace League\Flysystem\AwsS3V3;

use Aws\S3\S3ClientInterface;
use Generator;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\MimeType;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\Visibility;
use Psr\Http\Message\StreamInterface;
use Throwable;

class AwsS3V3Filesystem implements FilesystemAdapter
{
    /**
     * @var array
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

    public function __construct(
        S3ClientInterface $client,
        string $bucket,
        string $prefix = '',
        VisibilityConverter $visibility = null
    ) {
        $this->client = $client;
        $this->prefixer = new PathPrefixer($prefix);
        $this->bucket = $bucket;
        $this->visibility = $visibility ?: new PortableVisibilityConverter();
    }

    public function fileExists(string $path): bool
    {
        return $this->client->doesObjectExist($this->bucket, $this->prefixer->prefixPath($path));
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
        $acl = $this->determineAcl($config);
        $options = $this->createOptionsFromConfig($config);

        if ($body !== '' && ! array_key_exists('ContentType', $options) && $contentType = MimeType::detectMimeType(
                $key,
                $body
            )) {
            $options['ContentType'] = $contentType;
        }

        $this->client->upload($this->bucket, $key, $body, $acl, $options);
    }

    private function determineAcl(Config $config): string
    {
        $visibility = (string) $config->get('visibility', Visibility::PRIVATE);

        return $this->visibility->visibilityToAcl($visibility);
    }

    private function createOptionsFromConfig(Config $config): array
    {
        $options = [];

        foreach (static::AVAILABLE_OPTIONS as $option) {
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

    public function update(string $path, string $contents, Config $config): void
    {
        $this->upload($path, $contents, $config);
    }

    public function updateStream(string $path, $contents, Config $config): void
    {
        $this->upload($path, $contents, $config);
    }

    public function read(string $path): string
    {
        $body = $this->readObject($path);

        return (string) $body->getContents();
    }

    public function readStream(string $path)
    {
        $body = $this->readObject($path);

        return $body->detach();
    }

    public function delete(string $path): void
    {
        $arguments = ['Bucket' => $this->bucket, 'Key' => $this->prefixer->prefixPath($path)];
        $command = $this->client->getCommand('deleteObject', $arguments);

        try {
            $this->client->execute($command);
        } catch (Throwable $exception) {
            throw UnableToDeleteFile::atLocation($path, '', $exception);
        }
    }

    public function deleteDirectory(string $path): void
    {
        $prefix = $this->prefixer->prefixPath($path);
        $prefix = rtrim($prefix, '/') . '/';
        $this->client->deleteMatchingObjects($this->bucket, $prefix);
    }

    public function createDirectory(string $path, Config $config): void
    {
        $this->upload(rtrim($path, '/') . '/', '', $config);
    }

    public function setVisibility(string $path, $visibility): void
    {
        $arguments = [
            'Bucket' => $this->bucket,
            'Key'    => $this->prefixer->prefixPath($path),
            'ACL'    => $this->visibility->visibilityToAcl($visibility),
        ];
        $command = $this->client->getCommand('putObjectAcl', $arguments);

        try {
            $this->client->execute($command);
        } catch (Throwable $exception) {
            throw UnableToSetVisibility::atLocation($path, '', $exception);
        }
    }

    public function visibility(string $path): string
    {
        $arguments = ['Bucket' => $this->bucket, 'Key' => $this->prefixer->prefixPath($path)];
        $command = $this->client->getCommand('getObjectAcl', $arguments);

        try {
            $result = $this->client->execute($command);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::visibility($path, '', $exception);
        }

        return $this->visibility->aclToVisibility((array) $result->get('Grants'));
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

    public function listContents(string $path, bool $recursive): Generator
    {
    }

    public function move(string $source, string $destination, Config $config): void
    {
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $visibility = $this->visibility($source);
        } catch (Throwable $exception) {
            throw UnableToCopyFile::fromLocationTo(
                $source,
                $destination,
                $exception
            );
        }
        $arguments = [
            'ACL'        => $this->visibility->visibilityToAcl($visibility),
            'Bucket'     => $this->bucket,
            'Key'        => $this->prefixer->prefixPath($destination),
            'CopySource' => rawurlencode($this->bucket . '/' . $this->prefixer->prefixPath($source)),
        ];
        $command = $this->client->getCommand('copyObject', $arguments);

        try {
            $this->client->execute($command);
        } catch (Throwable $exception) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $exception);
        }
    }

    private function readObject(string $path): StreamInterface
    {
        $options = ['Bucket' => $this->bucket, 'Key' => $this->prefixer->prefixPath($path)];
        $command = $this->client->getCommand('getObject', $options);

        try {
            return $this->client->execute($command)->get('Body');
        } catch (Throwable $exception) {
            throw UnableToReadFile::fromLocation($path, '', $exception);
        }
    }
}
