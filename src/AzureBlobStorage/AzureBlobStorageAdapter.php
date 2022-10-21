<?php

declare(strict_types=1);

namespace League\Flysystem\AzureBlobStorage;

use DateTime;
use DateTimeInterface;
use League\Flysystem\ChecksumAlgoIsNotSupported;
use League\Flysystem\ChecksumProvider;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToGenerateTemporaryUrl;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToProvideChecksum;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\BlobSharedAccessSignatureHelper;
use MicrosoftAzure\Storage\Blob\Models\BlobProperties;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Common\Internal\Resources;
use MicrosoftAzure\Storage\Common\Internal\StorageServiceSettings;
use MicrosoftAzure\Storage\Common\Models\ContinuationToken;
use Throwable;
use function base64_decode;
use function bin2hex;
use function stream_get_contents;

class AzureBlobStorageAdapter implements FilesystemAdapter, PublicUrlGenerator, ChecksumProvider, TemporaryUrlGenerator
{
    /** @var string[] */
    private const META_OPTIONS = [
        'CacheControl',
        'ContentType',
        'Metadata',
        'ContentLanguage',
        'ContentEncoding',
    ];
    const ON_VISIBILITY_THROW_ERROR = 'throw';
    const ON_VISIBILITY_IGNORE = 'ignore';

    private BlobRestProxy $client;
    private MimeTypeDetector $mimeTypeDetector;
    private int $maxResultsForContentsListing;
    private string $container;
    private PathPrefixer $prefixer;
    private string $visibilityHandling;
    private ?StorageServiceSettings $serviceSettings;

    public function __construct(
        BlobRestProxy $client,
        string $container,
        string $prefix = '',
        MimeTypeDetector $mimeTypeDetector = null,
        int $maxResultsForContentsListing = 5000,
        string $visibilityHandling = self::ON_VISIBILITY_THROW_ERROR,
        StorageServiceSettings $serviceSettings = null,
    ) {
        $this->client = $client;
        $this->container = $container;
        $this->prefixer = new PathPrefixer($prefix);
        $this->mimeTypeDetector = $mimeTypeDetector ?? new FinfoMimeTypeDetector();
        $this->maxResultsForContentsListing = $maxResultsForContentsListing;
        $this->visibilityHandling = $visibilityHandling;
        $this->serviceSettings = $serviceSettings;
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $resolvedDestination = $this->prefixer->prefixPath($destination);
        $resolvedSource = $this->prefixer->prefixPath($source);

        try {
            $this->client->copyBlob(
                $this->container,
                $resolvedDestination,
                $this->container,
                $resolvedSource
            );
        } catch (Throwable $throwable) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $throwable);
        }
    }

    public function delete(string $path): void
    {
        $location = $this->prefixer->prefixPath($path);

        try {
            $this->client->deleteBlob($this->container, $location);
        } catch (Throwable $exception) {
            if ($exception instanceof ServiceException && $exception->getCode() === 404) {
                return;
            }

            throw UnableToDeleteFile::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function read(string $path): string
    {
        $response = $this->readStream($path);

        return stream_get_contents($response);
    }

    public function readStream(string $path)
    {
        $location = $this->prefixer->prefixPath($path);

        try {
            $response = $this->client->getBlob($this->container, $location);

            return $response->getContentStream();
        } catch (Throwable $exception) {
            throw UnableToReadFile::fromLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function listContents(string $path, bool $deep = false): iterable
    {
        $resolved = $this->prefixer->prefixDirectoryPath($path);

        $options = new ListBlobsOptions();
        $options->setPrefix($resolved);
        $options->setMaxResults($this->maxResultsForContentsListing);

        if ($deep === false) {
            $options->setDelimiter('/');
        }

        do {
            $response = $this->client->listBlobs($this->container, $options);

            foreach ($response->getBlobPrefixes() as $blobPrefix) {
                yield new DirectoryAttributes($this->prefixer->stripDirectoryPrefix($blobPrefix->getName()));
            }

            foreach ($response->getBlobs() as $blob) {
                yield $this->normalizeBlobProperties(
                    $this->prefixer->stripPrefix($blob->getName()),
                    $blob->getProperties()
                );
            }

            $continuationToken = $response->getContinuationToken();
            $options->setContinuationToken($continuationToken);
        } while ($continuationToken instanceof ContinuationToken);
    }

    public function fileExists(string $path): bool
    {
        $resolved = $this->prefixer->prefixPath($path);
        try {
            return $this->fetchMetadata($resolved) !== null;
        } catch (Throwable $exception) {
            if ($exception instanceof ServiceException && $exception->getCode() === 404) {
                return false;
            }
            throw UnableToCheckFileExistence::forLocation($path, $exception);
        }
    }

    public function directoryExists(string $path): bool
    {
        $resolved = $this->prefixer->prefixDirectoryPath($path);
        $options = new ListBlobsOptions();
        $options->setPrefix($resolved);
        $options->setMaxResults(1);

        try {
            $listResults = $this->client->listBlobs($this->container, $options);

            return count($listResults->getBlobs()) > 0;
        } catch (Throwable $exception) {
            throw UnableToCheckDirectoryExistence::forLocation($path, $exception);
        }
    }

    public function deleteDirectory(string $path): void
    {
        $resolved = $this->prefixer->prefixDirectoryPath($path);
        $options = new ListBlobsOptions();
        $options->setPrefix($resolved);

        try {
            start:
            $listResults = $this->client->listBlobs($this->container, $options);

            foreach ($listResults->getBlobs() as $blob) {
                $this->client->deleteBlob($this->container, $blob->getName());
            }

            $continuationToken = $listResults->getContinuationToken();

            if ($continuationToken instanceof ContinuationToken) {
                $options->setContinuationToken($continuationToken);
                goto start;
            }
        } catch (Throwable $exception) {
            throw UnableToDeleteDirectory::atLocation($path, $exception->getMessage(), $exception);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        // this is not supported by Azure
    }

    public function setVisibility(string $path, string $visibility): void
    {
        if ($this->visibilityHandling === self::ON_VISIBILITY_THROW_ERROR) {
            throw UnableToSetVisibility::atLocation($path, 'Azure does not support this operation.');
        }
    }

    public function visibility(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::visibility($path, 'Azure does not support visibility');
    }

    public function mimeType(string $path): FileAttributes
    {
        try {
            return $this->fetchMetadata($this->prefixer->prefixPath($path));
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::mimeType($path, $exception->getMessage(), $exception);
        }
    }

    public function lastModified(string $path): FileAttributes
    {
        try {
            return $this->fetchMetadata($this->prefixer->prefixPath($path));
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::lastModified($path, $exception->getMessage(), $exception);
        }
    }

    public function fileSize(string $path): FileAttributes
    {
        try {
            return $this->fetchMetadata($this->prefixer->prefixPath($path));
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::fileSize($path, $exception->getMessage(), $exception);
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

    public function write(string $path, string $contents, Config $config): void
    {
        $this->upload($path, $contents, $config);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->upload($path, $contents, $config);
    }

    /**
     * @param string|resource $contents
     */
    private function upload(string $destination, $contents, Config $config): void
    {
        $resolved = $this->prefixer->prefixPath($destination);
        try {
            $options = $this->getOptionsFromConfig($config);

            if (empty($options->getContentType())) {
                $options->setContentType($this->mimeTypeDetector->detectMimeType($resolved, $contents));
            }

            $this->client->createBlockBlob(
                $this->container,
                $resolved,
                $contents,
                $options
            );
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($destination, $exception->getMessage(), $exception);
        }
    }

    private function fetchMetadata(string $path): FileAttributes
    {
        return $this->normalizeBlobProperties(
            $path,
            $this->client->getBlobProperties($this->container, $path)->getProperties()
        );
    }

    private function getOptionsFromConfig(Config $config): CreateBlockBlobOptions
    {
        $options = new CreateBlockBlobOptions();

        foreach (self::META_OPTIONS as $option) {
            $setting = $config->get($option, '___NOT__SET___');

            if ($setting === '___NOT__SET___') {
                continue;
            }

            call_user_func([$options, "set$option"], $setting);
        }

        $mimeType = $config->get('mimetype');

        if ($mimeType !== null) {
            $options->setContentType($mimeType);
        }

        return $options;
    }

    private function normalizeBlobProperties(string $path, BlobProperties $properties): FileAttributes
    {
        return new FileAttributes(
            $path,
            $properties->getContentLength(),
            null,
            $properties->getLastModified()->getTimestamp(),
            $properties->getContentType(),
            ['md5_checksum' => $properties->getContentMD5()]
        );
    }

    public function publicUrl(string $path, Config $config): string
    {
        $location = $this->prefixer->prefixPath($path);

        return $this->client->getBlobUrl($this->container, $location);
    }

    public function checksum(string $path, Config $config): string
    {
        $algo = $config->get('checksum_algo', 'md5');

        if ($algo !== 'md5') {
            throw new ChecksumAlgoIsNotSupported();
        }

        try {
            $metadata = $this->fetchMetadata($this->prefixer->prefixPath($path));
            $checksum = $metadata->extraMetadata()['md5_checksum'] ?? '__not_specified';
        } catch (Throwable $exception) {
            throw new UnableToProvideChecksum($exception->getMessage(), $path, $exception);
        }

        if ($checksum === '__not_specified') {
            throw new UnableToProvideChecksum('No checksum provided in metadata', $path);
        }

        return bin2hex(base64_decode($checksum));
    }

    public function temporaryUrl(string $path, DateTimeInterface $expiresAt, Config $config): string
    {
        if ( ! $this->serviceSettings instanceof StorageServiceSettings) {
            throw UnableToGenerateTemporaryUrl::noGeneratorConfigured(
                $path,
                'The $serviceSettings constructor parameter must be set to generate temporary URLs.',
            );
        }

        try {
            $sas = new BlobSharedAccessSignatureHelper($this->serviceSettings->getName(), $this->serviceSettings->getKey());
            $baseUrl = $this->publicUrl($path, $config);
            $resourceName = $this->container . '/' . ltrim($this->prefixer->prefixPath($path), '/');
            $token = $sas->generateBlobServiceSharedAccessSignatureToken(
                Resources::RESOURCE_TYPE_BLOB,
                $resourceName,
                'r', // read
                DateTime::createFromInterface($expiresAt),
                $config->get('signed_start', ''),
                $config->get('signed_ip', ''),
                $config->get('signed_protocol', 'https'),
                $config->get('signed_identifier', ''),
                $config->get('cache_control', ''),
                $config->get('content_deposition', ''),
                $config->get('content_encoding', ''),
                $config->get('content_language', ''),
                $config->get('content_type', ''),
            );

            return "$baseUrl?$token";
        } catch (Throwable $exception) {
            throw UnableToGenerateTemporaryUrl::dueToError($path, $exception);
        }
    }
}
