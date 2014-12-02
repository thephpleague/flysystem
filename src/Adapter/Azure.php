<?php

namespace League\Flysystem\Adapter;

use WindowsAzure\Blob\Internal\IBlob;
use WindowsAzure\Blob\Models\BlobProperties;
use WindowsAzure\Blob\Models\ListBlobsOptions;
use WindowsAzure\Blob\Models\ListBlobsResult;
use WindowsAzure\Common\ServiceException;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Config;
use League\Flysystem\Util;

class Azure implements AdapterInterface
{
    use NotSupportingVisibilityTrait;

    /**
     * @var string
     */
    protected $container;

    /**
     * @var \WindowsAzure\Blob\Internal\IBlob
     */
    protected $client;

    /**
     * @param IBlob  $azureClient
     * @param string $container
     */
    public function __construct(IBlob $azureClient, $container)
    {
        $this->client = $azureClient;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        /** @var CopyBlobResult $result */
        $result = $this->client->createBlockBlob($this->container, $path, $contents);

        return $this->normalize($path, $result->getLastModified()->format('U'), $contents);
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->write($path, $resource, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {
        $this->client->copyBlob($this->container, $newpath, $this->container, $path);

        return $this->delete($path);
    }

    public function copy($path, $newpath)
    {
        /** @var CopyBlobResult $result */
        $this->client->copyBlob($this->container, $newpath, $this->container, $path);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        $this->client->deleteBlob($this->container, $path);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        $options = new ListBlobsOptions();
        $options->setPrefix($dirname);

        /** @var ListBlobsResult $listResults */
        $listResults = $this->client->listBlobs($this->container, $options);

        foreach ($listResults->getBlobs() as $blob) {
            /** @var Blob $blob */
            $this->client->deleteBlob($this->container, $blob->getName());
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        return array('path' => $dirname, 'type' => 'dir');
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        try {
            $this->client->getBlob($this->container, $path);
        } catch (ServiceException $e) {
            if ($e->getCode() !== 404) {
                throw $e;
            }

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        /** @var GetBlobResult $blobResult */
        $blobResult = $this->client->getBlob($this->container, $path);
        $properties = $blobResult->getProperties();
        $content = $this->streamContentsToString($blobResult->getContentStream());

        return $this->normalizeBlobProperties($path, $properties) + array('contents' => $content);
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        /** @var GetBlobResult $blobResult */
        $blobResult = $this->client->getBlob($this->container, $path);
        $properties = $blobResult->getProperties();

        return $this->normalizeBlobProperties($path, $properties) + array('stream' => $blobResult->getContentStream());
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        $options = new ListBlobsOptions();
        $options->setPrefix($directory);

        /** @var ListBlobsResult $listResults */
        $listResults = $this->client->listBlobs($this->container, $options);

        $contents = array();
        foreach ($listResults->getBlobs() as $blob) {
            $contents[] = $this->normalizeBlobProperties($blob->getName(), $blob->getProperties());
        }

        return $contents;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        /** @var GetBlobResult $result */
        $result = $this->client->getBlob($this->container, $path);

        return $this->normalizeBlobProperties($path, $result->getProperties());
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Builds the normalized output array
     *
     * @param string $path
     * @param int    $timestamp
     * @param mixed  $content
     *
     * @return array
     */
    protected function normalize($path, $timestamp, $content = null)
    {
        $data = array(
            'path'      => $path,
            'timestamp' => (int) $timestamp,
            'dirname'   => Util::dirname($path),
            'type'      => 'file',
        );

        if (is_string($content)) {
            $data['contents'] = $content;
        }

        return $data;
    }

    /**
     * Builds the normalized output array from a Blob object.
     *
     * @param string         $path
     * @param BlobProperties $properties
     *
     * @return array
     */
    protected function normalizeBlobProperties($path, BlobProperties $properties)
    {
        return array(
            'path'      => $path,
            'timestamp' => (int) $properties->getLastModified()->format('U'),
            'dirname'   => Util::dirname($path),
            'mimetype'  => $properties->getContentType(),
            'size'      => $properties->getContentLength(),
            'type'      => 'file',
        );
    }

    /**
     * Retrieves content streamed by Azure into a string
     *
     * @param  resource $resource
     * @return string
     */
    protected function streamContentsToString($resource)
    {
        return stream_get_contents($resource);
    }
}
