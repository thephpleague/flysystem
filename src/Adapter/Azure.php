<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Util;
use WindowsAzure\Blob\Internal\IBlob;
use WindowsAzure\Blob\Models\Blob;
use WindowsAzure\Blob\Models\CopyBlobResult;
use WindowsAzure\Blob\Models\GetBlobResult;
use WindowsAzure\Blob\Models\ListBlobsOptions;
use WindowsAzure\Blob\Models\ListBlobsResult;
use WindowsAzure\Common\ServiceException;

class Azure extends AbstractAdapter
{
    /**
     * @var string
     */
    protected $container;

    /**
     * @var \WindowsAzure\Blob\Internal\IBlob
     */
    protected $client;

    /**
     * {@inheritdoc}
     *
     * @param IBlob $azureClient
     * @param $container
     */
    public function __construct(IBlob $azureClient, $container)
    {
        $this->client = $azureClient;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, $visibility = null)
    {
        /** @var CopyBlobResult $result */
        $result = $this->client->createBlockBlob($this->container, $path, $contents);
        return $this->normalizeObject($path, $result->getLastModified()->format('U'), $contents);
    }

    /**
     * {@inheritdoc}
     *
     * @todo check what expected behaviour here is
     */
    public function writeStream($path, $resource, $config = null)
    {
        return $this->write($path, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents)
    {
        $this->write($path, $contents);
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {
        /** @var CopyBlobResult $result */
        $result = $this->client->copyBlob($this->container, $newpath, $this->container, $path);
        return $this->normalizeObject($path, $result->getLastModified()->format('U'));
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

        foreach($listResults->getBlobs() as $blob){
            /** @var Blob $blob */
            $this->client->deleteBlob($this->container, $blob->getName());
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname)
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
            return true;
        } catch (ServiceException $e) {

            if ($e->getCode() != 404) {
                throw $e;
            }

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        /** @var GetBlobResult $blobResult */
        $blobResult = $this->client->getBlob($this->container, $path);
        $properties = $blobResult->getProperties();

        return $this->normalizeObject(
            $path,
            $properties->getLastModified()->format('U'),
            $this->streamContentsToString($blobResult->getContentStream())
        );
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
        foreach($listResults->getBlobs() as $blob){
            /** @var Blob $blob */
            $contents[] = $this->read($blob->getName());
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @todo confirm expected return content for this function
     */
    public function getMetadata($path)
    {
        return $this->read($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        /** @var GetBlobResult $result */
        $result = $this->client->getBlob($this->container, $path);
        return $result->getProperties()->getContentLength();
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        /** @var GetBlobResult $result */
        $result = $this->client->getBlob($this->container, $path);
        return $result->getProperties()->getContentType();
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        /** @var GetBlobResult $result */
        $result = $this->client->getBlob($this->container, $path);
        return $result->getProperties()->getLastModified();
    }

    /**
     * Builds the normalized output array
     *
     * @todo confirm expected content of normalized return data
     *
     * @param string $path
     * @param int $timestamp
     * @param string $content
     * @return array
     */
    protected function normalizeObject( $path, $timestamp, $content = null)
    {
        return array(
            'path'      => $path,
            'timestamp' => $timestamp,
            'dirname'   => Util::dirname($path),
            'contents'  => $content,
            'type'      => 'file'
        );
    }

    /**
     * Retrieves content streamed by Azure into a string
     *
     * @param resource $resource
     * @return string
     */
    protected function streamContentsToString($resource)
    {
        return stream_get_contents($resource);
    }
}
