<?php

namespace League\Flysystem\Adapter;

use Dropbox\Client;
use Dropbox\Exception;
use Dropbox\WriteMode;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Config;
use League\Flysystem\Util;

class Dropbox extends AbstractAdapter
{
    use NotSupportingVisibilityTrait;

    /**
     * @var  array  $resultMap
     */
    protected static $resultMap = [
        'bytes'          => 'size',
        'mime_type'      => 'mimetype',
    ];

    /**
     * @var  Client  $client
     */
    protected $client;

    /**
     * Constructor
     *
     * @param Client $client
     * @param string $prefix
     */
    public function __construct(Client $client, $prefix = null)
    {
        $this->client = $client;
        $this->setPathPrefix($prefix);
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        return $this->upload($path, $contents, WriteMode::add());
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->uploadStream($path, $resource, WriteMode::add());
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        return $this->upload($path, $contents, WriteMode::force());
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->uploadStream($path, $resource, WriteMode::force());
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        if (! $object = $this->readStream($path)) {
            return false;
        }

        $object['contents'] = stream_get_contents($object['stream']);
        fclose($object['stream']);
        unset($object['stream']);

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        $stream = fopen('php://temp', 'w+');
        $location = $this->applyPathPrefix($path);

        if (! $this->client->getFile($location, $stream)) {
            fclose($stream);

            return false;
        }

        rewind($stream);

        return compact('stream');
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {
        $path = $this->applyPathPrefix($path);
        $newpath = $this->applyPathPrefix($newpath);

        try {
            $this->client->move($path, $newpath);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        $path = $this->applyPathPrefix($path);
        $newpath = $this->applyPathPrefix($newpath);

        try {
            $this->client->copy($path, $newpath);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        $location = $this->applyPathPrefix($path);

        return $this->client->delete($location);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($path)
    {
        return $this->delete($path);
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($path, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        $result = $this->client->createFolder($location);

        if ($result === null) {
            return false;
        }

        return $this->normalizeResponse($result, $path);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        $location = $this->applyPathPrefix($path);
        $object = $this->client->getMetadata($location);

        if (! $object) {
            return false;
        }

        return $this->normalizeResponse($object, $path);
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
    public function getSize($path)
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
     * {@inheritdoc}
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        $listing = [];
        $directory = trim($directory, '/.');
        $location = $this->applyPathPrefix($directory);

        if (! $result = $this->client->getMetadataWithChildren($location)) {
            return [];
        }

        foreach ($result['contents'] as $object) {
            $path = $this->removePathPrefix($object['path']);
            $listing[] = $this->normalizeResponse($object, $path);

            if ($recursive && $object['is_dir']) {
                $listing = array_merge($listing, $this->listContents($path, true));
            }
        }

        return $listing;
    }

    /**
     * Apply the path prefix
     *
     * @param string $path
     *
     * @return string prefixed path
     */
    public function applyPathPrefix($path)
    {
        $path = parent::applyPathPrefix($path);

        return '/'.rtrim($path, '/');
    }

    /**
     * Do the actual upload of a string file
     *
     * @param string    $path
     * @param string    $contents
     * @param WriteMode $mode
     *
     * @return array|false file metadata
     */
    protected function upload($path, $contents, WriteMode $mode)
    {
        $location = $this->applyPathPrefix($path);

        if (! $result = $this->client->uploadFileFromString($location, $mode, $contents)) {
            return false;
        }

        return $this->normalizeResponse($result, $path);
    }

    /**
     * Do the actual upload of a file resource
     *
     * @param string    $path
     * @param resource  $resource
     * @param WriteMode $mode
     *
     * @return array|false file metadata
     */
    protected function uploadStream($path, $resource, WriteMode $mode)
    {
        $location = $this->applyPathPrefix($path);

        if (! $result = $this->client->uploadFile($location, $mode, $resource)) {
            return false;
        }

        return $this->normalizeResponse($result, $path);
    }

    /**
     * Normalize a Dropbox response
     *
     * @param        $response
     * @param string $path
     *
     * @return array
     */
    protected function normalizeResponse(array $response, $path = null)
    {
        $result = ['path' => trim($path ?: $response['path'], '/')];

        if (isset($response['modified'])) {
            $result['timestamp'] = strtotime($response['modified']);
        }

        $result = array_merge($result, Util::map($response, static::$resultMap));
        $result['type'] = $response['is_dir'] ? 'dir' : 'file';

        return $result;
    }
}
