<?php

namespace Flysystem\Adapter;

use Dropbox\Client;
use Dropbox\WriteMode;
use Flysystem\AdapterInterface;
use Flysystem\Util;
use LogicException;

class Dropbox extends AbstractAdapter
{
    protected static $resultMap = array(
        'bytes'          => 'size',
        'mime_type'      => 'mimetype',
    );

    protected $client;
    protected $prefix;

    /**
     * Constructor
     *
     * @param  \Dropbox\Client  $client
     * @param  string           $prefix
     */
    public function __construct(Client $client, $prefix = null)
    {
        $this->client = $client;

        if ($prefix) {
            $this->prefix = '/'.ltrim($prefix, '/');
        }
    }

    public function has($path)
    {
        return $this->getMetadata($path);
    }

    public function write($path, $contents, $visibility = null)
    {
        return $this->upload($path, $contents, WriteMode::add());
    }

    public function writeStream($path, $resource, $visibility = null)
    {
        $this->uploadStream($path, $resource, $visibility, WriteMode::add());
    }

    public function update($path, $contents)
    {
        return $this->upload($path, $contents, WriteMode::force());
    }

    public function updateStream($path, $resource, $visibility = null)
    {
        $this->uploadStream($path, $resource, $visibility, WriteMode::force());
    }

    public function upload($path, $contents, $mode)
    {
        $result = $this->client->uploadFileFromString($this->prefix($path), $mode, $contents);

        return $this->normalizeObject($result, $path);
    }

    protected function uploadStream($path, $resource, $mode)
    {
        $result = $this->client->uploadFile($path, $mode, $resource);

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

        if ( ! $this->client->getFile($this->prefix($path), $stream)) {
            fclose($stream);
            return false;
        }

        rewind($stream);

        return compact('stream');
    }

    public function rename($path, $newpath)
    {
        $options = $this->getOptions($newpath, array(
            'Bucket' => $this->bucket,
            'CopySource' => $this->bucket.'/'.$this->prefix($path),
        ));

        $result = $this->client->copyObject($options)->getAll();
        $result = $this->normalizeObject($result, $newpath);
        $this->delete($path);

        return $result;
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
        return $this->retrieveListing($this->prefix($directory), $recursive);
    }

    public function retrieveListing($dir, $recursive = true)
    {
        $listing = array();
        $directory = rtrim($dir, '/');
        $length = strlen($directory) + 1;
        $result = $this->client->getMetadataWithChildren($directory);

        foreach ($result['contents'] as $object)
        {
            $listing[] = $this->normalizeObject($object, substr($object['path'], $length));

            if ($recursive and $object['is_dir']) {
                $listing = array_merge($listing, $this->retrieveListing($object['path']));
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
        if ( ! $this->prefix) {
            return '/'.ltrim($path, '/');
        }

        return $this->prefix.'/'.$path;
    }
}
