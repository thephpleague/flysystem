<?php

declare(strict_types=1);

namespace League\Flysystem\AzureBlobStorage;

use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\BlobProperties;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Common\Models\ContinuationToken;
use Throwable;

use function sprintf;
use function stream_get_contents;

class AzureBlobStorageAdapter implements FilesystemAdapter
{
    /** @var string[] */
    private const META_OPTIONS = [
        'CacheControl',
        'ContentType',
        'Metadata',
        'ContentLanguage',
        'ContentEncoding',
    ];
    /** @var BlobRestProxy */
    private $client;
    /** @var PathResolverInterface */
    private $pathResolver;
    /** @var MimeTypeDetector */
    private $mimeTypeDetector;
    /** @var int */
    private $maxResultsForContentsListing;

    public function __construct(
        BlobRestProxy $client,
        PathResolverInterface $pathResolver,
        MimeTypeDetector $mimeTypeDetector = null,
        int $maxResultsForContentsListing = 5000
    ) {
        $this->client = $client;
        $this->pathResolver = $pathResolver;
        $this->mimeTypeDetector = $mimeTypeDetector ?? new FinfoMimeTypeDetector();
        $this->maxResultsForContentsListing = $maxResultsForContentsListing;
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $resolvedDestination = $this->pathResolver->resolve($destination);
        $resolvedSource = $this->pathResolver->resolve($source);

        $this->client->copyBlob(
            $resolvedDestination->getContainer(),
            $resolvedDestination->getPath(),
            $resolvedSource->getContainer(),
            $resolvedSource->getPath()
        );
    }

    public function delete(string $path): void
    {
        $resolved = $this->pathResolver->resolve($path);
        try {
            $this->client->deleteBlob($resolved->getContainer(), $resolved->getPath());
        } catch (Throwable $exception) {
            throw UnableToDeleteFile::atLocation($path, '', $exception);
        }
    }

    public function read(string $path): string
    {
        $response = $this->readStream($path);

        return stream_get_contents($response);
    }

    public function readStream($path)
    {
        $resolved = $this->pathResolver->resolve($path);
        try {
            $response = $this->client->getBlob($resolved->getContainer(), $resolved->getPath());

            return $response->getContentStream();
        } catch (Throwable $exception) {
            throw UnableToReadFile::fromLocation($path);
        }
    }

    public function listContents(string $path, bool $deep = false): iterable
    {
        $resolved = $this->pathResolver->resolve($path);

        $options = new ListBlobsOptions();
        $options->setPrefix($resolved->getPath());
        $options->setMaxResults($this->maxResultsForContentsListing);
        $options->setDelimiter('/');

        do {
            $response = $this->client->listBlobs($resolved->getContainer(), $options);

            foreach ($response->getBlobs() as $blob) {
                $name = $blob->getName();
                yield $this->normalizeBlobProperties($name, $blob->getProperties());
            }

            if (!$deep) {
                foreach ($response->getBlobPrefixes() as $blobPrefix) {
                    yield new DirectoryAttributes($blobPrefix->getName());
                }
            }

            $continuationToken = $response->getContinuationToken();
            $options->setContinuationToken($continuationToken);
        } while ($continuationToken instanceof ContinuationToken);
    }

    public function fileExists(string $path): bool
    {
        $resolved = $this->pathResolver->resolve($path);
        try {
            return $this->getMetadata($resolved) !== null;
        } catch (Throwable $exception) {
            if ($exception instanceof ServiceException && $exception->getCode() === 404) {
                return false;
            }
            throw UnableToCheckFileExistence::forLocation($path, $exception);
        }
    }

    public function deleteDirectory(string $path): void
    {
        $resolved = $this->pathResolver->resolve($path);

        try {
            $options = new ListBlobsOptions();
            $options->setPrefix($resolved->getPath());
            $listResults = $this->client->listBlobs($resolved->getContainer(), $options);
            foreach ($listResults->getBlobs() as $blob) {
                $this->client->deleteBlob($resolved->getContainer(), $blob->getName());
            }
        } catch (Throwable $exception) {
            throw UnableToDeleteDirectory::atLocation($path, '', $exception);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        $createContainerOptions = new CreateContainerOptions();
        $createContainerOptions->setPublicAccess(PublicAccessType::BLOBS_ONLY);

        try {
            $this->client->createContainer(
                $this->pathResolver->resolve($path)->getContainer(),
                $createContainerOptions
            );
        } catch (Throwable $exception) {
            if ($exception instanceof ServiceException && 409 === $exception->getCode()) {
                throw UnableToCreateDirectory::dueToFailure($path, $exception);
            }
            throw UnableToCreateDirectory::dueToFailure(
                sprintf('Unable to create container "%s".', $path),
                $exception
            );
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
    }

    public function visibility(string $path): FileAttributes
    {
        try {
            return $this->getMetadata($this->pathResolver->resolve($path));
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::visibility($path, '', $exception);
        }
    }

    public function mimeType(string $path): FileAttributes
    {
        try {
            return $this->getMetadata($this->pathResolver->resolve($path));
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::mimeType($path, '', $exception);
        }
    }

    public function lastModified(string $path): FileAttributes
    {
        try {
            return $this->getMetadata($this->pathResolver->resolve($path));
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::lastModified($path, '', $exception);
        }
    }

    public function fileSize(string $path): FileAttributes
    {
        try {
            return $this->getMetadata($this->pathResolver->resolve($path));
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::fileSize($path, '', $exception);
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

    private function upload(string $destination, $contents, Config $config): void
    {
        $resolved = $this->pathResolver->resolve($destination);
        try {
            $options = $this->getOptionsFromConfig($config);

            if (empty($options->getContentType())) {
                $options->setContentType($this->mimeTypeDetector->detectMimeType($resolved->getPath(), $contents));
            }

            $this->client->createBlockBlob(
                $resolved->getContainer(),
                $resolved->getPath(),
                $contents,
                $options
            );
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($destination, '', $exception);
        }
    }

    private function getMetadata(Path $path): FileAttributes
    {
        return $this->normalizeBlobProperties(
            $path->getPath(),
            $this->client->getBlobProperties($path->getContainer(), $path->getPath())->getProperties()
        );
    }

    private function getOptionsFromConfig(Config $config): CreateBlockBlobOptions
    {
        $options = $config->get('blobOptions', new CreateBlockBlobOptions());
        foreach (self::META_OPTIONS as $option) {
            if (!$config->get($option)) {
                continue;
            }
            call_user_func([$options, "set$option"], $config->get($option));
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
            $properties->getContentType()
        );
    }
}
