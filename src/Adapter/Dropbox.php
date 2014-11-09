<?php

namespace League\Flysystem\Adapter;

use Dropbox\Client;
use Dropbox\WriteMode;
use Dropbox\Exception;
use League\Flysystem\Config;
use League\Flysystem\Util;

class Dropbox extends AbstractAdapter
{
    /**
     * @var  array  $resultMap
     */
    protected static $resultMap = array(
        'bytes'          => 'size',
        'mime_type'      => 'mimetype',
    );

    /**
     * @var  \Dropbox\Client  $client
     */
    protected $client;

    /**
     * Constructor
     *
     * @param  \Dropbox\Client  $client
     * @param  string           $prefix
     */
    public function __construct(Client $client, $prefix = null)
    {
        $this->client = $client;
        $this->setPathPrefix($prefix);
    }

    /**
     * Check weather a file exists
     *
     * @param   string       $path
     * @return  false|array  false or file metadata
     */
    public function has($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Write a file
     *
     * @param   string  $path
     * @param   string  $contents
     * @param   mixed   $config
     * @return  array   file metadata
     */
    public function write($path, $contents, Config $config)
    {
        return $this->upload($path, $contents, WriteMode::add());
    }

    /**
     * Write a file using a stream
     *
     * @param   string    $path
     * @param   resource  $resource
     * @param   mixed     $config
     * @return  array     file metadata
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->uploadStream($path, $resource, WriteMode::add());
    }

    /**
     * Update a file
     *
     * @param   string  $path
     * @param   string  $contents
     * @param   Config  $config   Config object or visibility setting
     * @return  array   file metadata
     */
    public function update($path, $contents, Config $config)
    {
        return $this->upload($path, $contents, WriteMode::force());
    }

    /**
     * Update a file using a stream
     *
     * @param   string    $path
     * @param   resource  $resource
     * @param   mixed     $config   Config object or visibility setting
     * @return  array     file metadata
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->uploadStream($path, $resource, WriteMode::force());
    }

    /**
     * Do the actual upload of a string file
     *
     * @param   string  $path
     * @param   string  $contents
     * @param   WriteMode  $mode
     * @return  array|false   file metadata
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
     * @param   string  $path
     * @param   resource  $resource
     * @param   WriteMode  $mode
     * @return  array|false   file metadata
     */
    protected function uploadStream($path, $resource, WriteMode $mode)
    {
        $location = $this->applyPathPrefix($path);

        if (! $result = $this->client->uploadFile($location, $mode, $resource)) {
            return false;
        }

        return $this->normalizeResponse($result, $path);
    }

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

    public function delete($path)
    {
        $location = $this->applyPathPrefix($path);

        return $this->client->delete($location);
    }

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
        $listing = array();
        $directory = trim($directory, '/.');
        $location = $this->applyPathPrefix($directory);

        if (! $result = $this->client->getMetadataWithChildren($location)) {
            return array();
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
     * Normalize a Dropbox response
     *
     * @param      $object
     * @param string|null $path
     * @return array
     */
    protected function normalizeResponse($object, $path = null)
    {
        $result = array('path' => trim($path ?: $object['path'], '/'));

        if (isset($object['modified'])) {
            $result['timestamp'] = strtotime($object['modified']);
        }

        $result = array_merge($result, Util::map($object, static::$resultMap));
        $result['type'] = $object['is_dir'] ? 'dir' : 'file';

        return $result;
    }

    /**
     * Apply the path prefix
     *
     * @param   string  $path
     * @return  string  prefixed path
     */
    public function applyPathPrefix($path)
    {
        $path = parent::applyPathPrefix($path);

        return '/' . rtrim($path, '/');
    }
}
