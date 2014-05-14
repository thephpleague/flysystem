<?php

namespace League\Flysystem\Adapter;

use Dropbox\Client;
use Dropbox\WriteMode;
use Dropbox\Exception;
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
     * @var  Dropbox\Client  $client
     */
    protected $client;

    /**
     * @var  string  $prefix
     */
    protected $prefix;

    /**
     * Constructor
     *
     * @param  \Dropbox\Client  $client
     * @param  string           $prefix
     */
    public function __construct(Client $client, $prefix = null)
    {
        $prefix = trim($prefix, '/');
        $this->client = $client;
        $this->prefix = '/' . $prefix;
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
    public function write($path, $contents, $config = null)
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
    public function writeStream($path, $resource, $config = null)
    {
        return $this->uploadStream($path, $resource, WriteMode::add());
    }

    /**
     * Update a file
     *
     * @param   string  $path
     * @param   string  $contents
     * @param   mixed   $config   Config object or visibility setting
     * @return  array   file metadata
     */
    public function update($path, $contents, $config = null)
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
    public function updateStream($path, $resource, $config = null)
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
        $location = $this->prefix($path);

        if ( ! $result = $this->client->uploadFileFromString($location, $mode, $contents)) {
            return false;
        }

        return $this->normalizeObject($result, $path);
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
        $location = $this->prefix($path);

        if ( ! $result = $this->client->uploadFile($location, $mode, $resource)) {
            return false;
        }

        return $this->normalizeObject($result, $path);
    }

    public function read($path)
    {
        if ( ! $object = $this->readStream($path)) {
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
        $location = $this->prefix($path);

        if ( ! $this->client->getFile($location, $stream)) {
            fclose($stream);
            return false;
        }

        rewind($stream);

        return compact('stream');
    }

    public function rename($path, $newpath)
    {
        $path = $this->prefix($path);
        $newpath = $this->prefix($newpath);

        try {
            $result = $this->client->move($path, $newpath);
        } catch (Exception $e) {
            return false;
        }

        return $this->normalizeObject($result);
    }

    public function copy($path, $newpath)
    {
        $path = $this->prefix($path);
        $newpath = $this->prefix($newpath);

        try {
            $result = $this->client->copy($path, $newpath);
        } catch (Exception $e) {
            return false;
        }

        return $this->normalizeObject($result);
    }

    public function delete($path)
    {
        return $this->client->delete($this->prefix($path));
    }

    public function deleteDir($path)
    {
        return $this->delete($path);
    }

    public function createDir($path)
    {
        return array('path' => $path, 'type' => 'dir');
    }

    public function getMetadata($path)
    {
        $object = $this->client->getMetadata($this->prefix($path));

        if ( ! $object) {
            return false;
        }

        return $this->normalizeObject($object, $path);
    }

    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    public function listContents($directory = '', $recursive = false)
    {
        $listing = array();
        $directory = trim($directory, '/.');
        $prefixLength = strlen($this->prefix);
        $location = '/' . trim($this->prefix($directory), '/');

        if ( ! $result = $this->client->getMetadataWithChildren($location)) {
            return array();
        }

        foreach ($result['contents'] as $object) {
            $path = substr($object['path'], $prefixLength);
            $listing[] = $this->normalizeObject($object, trim($path, '/'));

            if ($recursive && $object['is_dir']) {
                $listing = array_merge($listing, $this->listContents($path));
            }
        }

        return $listing;
    }

    protected function normalizeObject($object, $path = null)
    {
        $result = array('path' => $path ?: ltrim($object['path'], '/'));

        if (isset($object['modified'])) {
            $result['timestamp'] = strtotime($object['modified']);
        }

        $result = array_merge($result, Util::map($object, static::$resultMap));
        $result['type'] = $object['is_dir'] ? 'dir' : 'file';

        return $result;
    }

    protected function prefix($path)
    {
        $prefix = rtrim($this->prefix, '/');

        return $prefix . '/' . ltrim($path, '/');
    }
}
