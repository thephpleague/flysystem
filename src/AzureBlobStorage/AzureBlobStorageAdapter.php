<?php

declare(strict_types=1);

namespace League\Flysystem\AzureBlobStorage;

use GuzzleHttp\Psr7\Utils;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\BlobPrefix;
use MicrosoftAzure\Storage\Blob\Models\BlobProperties;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Common\Models\ContinuationToken;
use function array_map;
use function array_merge;
use function call_user_func;
use function rtrim;
use function stream_get_contents;
use function strlen;
use function strpos;
use function substr;

final class AzureBlobStorageAdapter implements FilesystemAdapter
{
    /**
     * @var BlobRestProxy
     */
    private $client;

    /**
     * @var string
     */
    private $container;

    /**
     * @var PathPrefixer
     */
    private $prefixer;

    /**
     * @var MimeTypeDetector
     */
    private $mimeTypeDetector;

    /**
     * @var string[]
     */
    protected static $metaOptions = [
        'CacheControl',
        'ContentType',
        'Metadata',
        'ContentLanguage',
        'ContentEncoding',
    ];

    /**
     * @var int
     */
    private $maxResultsForContentsListing = 5000;

    public function __construct(
        BlobRestProxy $client,
        string $container,
        string $prefix = null,
        MimeTypeDetector $mimeTypeDetector = null
    ) {
        $this->client = $client;
        $this->container = $container;
        $this->prefixer = new PathPrefixer($prefix);
        $this->mimeTypeDetector = $mimeTypeDetector ?: new FinfoMimeTypeDetector();
    }

    public function fileExists(string $path) : bool
    {
        try {
            $this->client->getBlobProperties($this->container, $this->prefixer->prefixPath($path));

            return true;
        } catch (ServiceException $exception) {
            if ($exception->getCode() !== 404) {
                throw $exception;
            }

            return false;
        }
    }

    public function write(string $path, string $contents, Config $config) : void
    {
        $this->upload($path, $contents, $config);
    }

    public function writeStream(string $path, $contents, Config $config) : void
    {
        $this->upload($path, $contents, $config);
    }

    /**
     * @param string $path
     * @param string|resource $contents
     * @param Config $config
     */
    protected function upload(string $path, $contents, Config $config) : void
    {
        $destination = $this->prefixer->prefixPath($path);

        $options = $this->getOptionsFromConfig($config);

        if (empty($options->getContentType())) {
            $options->setContentType($this->mimeTypeDetector->detectMimeType($path, $contents));
        }

        /**
         * We manually create the stream to prevent it from closing the resource
         * in its destructor.
         */
        $stream = Utils::streamFor($contents);
        $this->client->createBlockBlob(
            $this->container,
            $destination,
            $contents,
            $options
        );

        $stream->detach();
    }

    public function read(string $path) : string
    {
        $resource = $this->readStream($path);

        return stream_get_contents($resource);
    }

    public function readStream(string $path)
    {
        try {
            $response = $this->client->getBlob(
                $this->container,
                $this->prefixer->prefixPath($path)
            );

            return $response->getContentStream();
        } catch (ServiceException $exception) {
            if ($exception->getCode() !== 404) {
                throw UnableToReadFile::fromLocation($path, '', $exception);
            }

            throw $exception;
        }
    }

    public function delete(string $path) : void
    {
        try {
            $this->client->deleteBlob($this->container, $this->prefixer->prefixPath($path));
        } catch (ServiceException $exception) {
            if ($exception->getCode() !== 404) {
                throw UnableToDeleteFile::atLocation($path, '', $exception);
            }
        }
    }

    public function deleteDirectory(string $path) : void
    {
        $prefix = $this->prefixer->prefixPath($path);
        $options = new ListBlobsOptions();
        $options->setPrefix($prefix . '/');
        $listResults = $this->client->listBlobs($this->container, $options);
        foreach ($listResults->getBlobs() as $blob) {
            $this->client->deleteBlob($this->container, $blob->getName());
        }
    }

    public function createDirectory(string $path, Config $config) : void
    {
    }

    public function setVisibility(string $path, string $visibility) : void
    {
        // TODO: Implement setVisibility() method.
    }

    public function visibility(string $path) : FileAttributes
    {
        // TODO: Implement visibility() method.
    }

    private function fetchFileAttributes(string $path) : FileAttributes
    {
        $properties = $this->client->getBlobProperties($this->container, $this->prefixer->prefixPath($path))->getProperties();

        return $this->normalizeFileAttributes($path, $properties);
    }

    private function normalizeFileAttributes(string $path, BlobProperties $properties) : FileAttributes
    {
        if (substr($path, -1) === '/') {
            return new FileAttributes($path);
        }

        return new FileAttributes(
            $path,
            $properties->getContentLength(),
            null,
            (int) $properties->getLastModified()->format('U'),
            $properties->getContentType()
        );
    }

    public function mimeType(string $path) : FileAttributes
    {
        $attributes = $this->fetchFileAttributes($path);

        if ($attributes->mimeType() === null) {
            throw UnableToRetrieveMetadata::mimeType($path);
        }

        return $attributes;
    }

    public function lastModified(string $path) : FileAttributes
    {
        $attributes = $this->fetchFileAttributes($path);

        if ($attributes->lastModified() === null) {
            throw UnableToRetrieveMetadata::lastModified($path);
        }

        return $attributes;
    }

    public function fileSize(string $path) : FileAttributes
    {
        $attributes = $this->fetchFileAttributes($path);

        if ($attributes->fileSize() === null) {
            throw UnableToRetrieveMetadata::fileSize($path);
        }

        return $attributes;
    }

    public function listContents(string $path, bool $deep) : iterable
    {
        $result = [];
        $location = $this->prefixer->prefixPath($path);

        if (strlen($location) > 0) {
            $location = rtrim($location, '/') . '/';
        }

        $options = new ListBlobsOptions();
        $options->setPrefix($location);
        $options->setMaxResults($this->maxResultsForContentsListing);

        if ( ! $deep) {
            $options->setDelimiter('/');
        }

        list_contents:
        $response = $this->client->listBlobs($this->container, $options);
        $continuationToken = $response->getContinuationToken();
        foreach ($response->getBlobs() as $blob) {
            $name = $blob->getName();

            if ($location === '' || strpos($name, $location) === 0) {
                $result[] = $this->normalizeFileAttributes($name, $blob->getProperties());
            }
        }

        if ( ! $deep) {
            $result = array_merge(
                $result,
                array_map(
                    function (BlobPrefix $prefix) {
                        return $this->normalizeBlobPrefix($prefix);
                    },
                    $response->getBlobPrefixes()
                )
            );
        }

        if ($continuationToken instanceof ContinuationToken) {
            $options->setContinuationToken($continuationToken);
            goto list_contents;
        }

        return $result;
    }

    public function move(string $source, string $destination, Config $config) : void
    {
        $this->copy($source, $destination, $config);
        $this->delete($source);
    }

    public function copy(string $source, string $destination, Config $config) : void
    {
        $source = $this->prefixer->prefixPath($source);
        $destination = $this->prefixer->prefixPath($destination);
        $this->client->copyBlob($this->container, $destination, $this->container, $source);
    }

    protected function getOptionsFromConfig(Config $config): CreateBlockBlobOptions
    {
        $options = $config->get('blobOptions', new CreateBlockBlobOptions());
        foreach (static::$metaOptions as $option) {
            if ( ! $config->get($option)) {
                continue;
            }
            call_user_func([$options, "set$option"], $config->get($option));
        }
        if ($mimetype = $config->get('mimetype')) {
            $options->setContentType($mimetype);
        }

        return $options;
    }

    protected function normalizeBlobPrefix(BlobPrefix $blobPrefix): array
    {
        return ['type' => 'dir', 'path' => $this->prefixer->stripPrefix(rtrim($blobPrefix->getName(), '/'))];
    }
}
