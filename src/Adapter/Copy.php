<?php

namespace League\Flysystem\Adapter;

use Barracuda\Copy\API;
use League\Flysystem\Config;
use League\Flysystem\Util;

class Copy extends AbstractAdapter
{
    protected static $resultMap = array(
        'size'           => 'size',
        'mime_type'      => 'mimetype',
        'type'           => 'type',
    );

    protected $client;
    protected $prefix;

    /**
     * Constructor
     *
     * @param  \Barracuda\Copy\API   $client
     * @param  string                $prefix
     */
    public function __construct(\Barracuda\Copy\API $client, $prefix = null)
    {
        $this->client = $client;
        $this->prefix = $prefix;
    }

    /**
     * Check weather a file exists
     *
     * @param   string       $path
     * @return  false|array  false or file metadata
     */
    public function has($path)
    {
        return $this->getMetadata($this->applyPathPrefix($path));
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
        $result = $this->client->uploadFromString($this->applyPathPrefix($path), $contents);
        return $this->normalizeObject($result, $path);
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
        $result = $this->client->uploadFromStream($this->applyPathPrefix($path), $resource);
        return $this->normalizeObject($result, $path);
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
        $result = $this->client->uploadFromString($this->applyPathPrefix($path), $contents);
        return $this->normalizeObject($result, $path);
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
        $result = $this->client->uploadFromStream($this->applyPathPrefix($path), $resource);
        return $this->normalizeObject($result, $path);
    }

    /**
     * Read a file
     *
     * @param   string  $path
     * @return  array   contains key of contents that has binary data
     */
    public function read($path)
    {
        return $this->client->readToString($this->applyPathPrefix($path));
    }

    /**
     * Get a read-stream for a file
     *
     * @param   string  $path
     * @return  array   contains key of stream that has resource
     */
    public function readStream($path)
    {
        return $this->client->readToStream($this->applyPathPrefix($path));
    }

    /**
     * Rename an object (file or dir)
     *
     * @param   string  $path
     * @param   string  $newpath
     * @return  array   file metadata
     */
    public function rename($path, $newpath)
    {
        $path = $this->applyPathPrefix($path);
        $newpath = $this->applyPathPrefix($newpath);

        if ( ! $result = $this->client->rename($path, $newpath)) {
            return false;
        }

        return $this->normalizeObject($result);
    }

    /**
     * Copy a file
     *
     * @param   string  $path
     * @param   string  $newpath
     * @return  array   file metadata
     */
    public function copy($path, $newpath)
    {
        $result = $this->client->copy($path, $newpath);

        return $this->normalizeObject($result, $newpath);
    }

    /**
     * Delete a file
     *
     * @param   string   $path
     * @return  boolean  delete result
     */
    public function delete($path)
    {
        return $this->client->removeFile($this->applyPathPrefix($path));
    }

    /**
     * Delete a directory (recursive)
     *
     * @param   string   $path
     * @return  boolean  delete result
     */
    public function deleteDir($path)
    {
        return $this->client->removeDir($this->applyPathPrefix($path));
    }

    /**
     * Create a directory
     *
     * @param   string        $path directory name
     * @param   array|Config  $options
     *
     * @return  bool
     */
    public function createDir($path, $config = null)
    {
        return $this->client->createDir($this->applyPathPrefix($path));
    }


    /**
     * Get metadata for a file
     *
     * @param   string  $path
     * @return  array   file metadata
     */
    public function getMetadata($path)
    {
        $objects = $this->client->listPath($this->applyPathPrefix($path));

        if ($objects === false || isset($objects[0]) === false || empty($objects[0])) {
            return false;
        }

        return $this->normalizeObject($objects[0], $path);
    }

    /**
     * Get the mimetype of a file
     *
     * @param   string  $path
     * @return  array   file metadata
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the size of a file
     *
     * @param   string  $path
     * @return  array   file metadata
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the timestamp of a file
     *
     * @param   string  $path
     * @return  array   file metadata
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * List contents of a directory
     *
     * @param   string  $dirname
     * @param   bool    $recursive
     * @return  array   directory contents
     */
    public function listContents($directory = '', $recursive = false)
    {
        $listing = array();

        if ( ! $result = $this->client->listPath($this->applyPathPrefix($directory))) {
            return false;
        }

        foreach ($result as $object)
        {
            $listing[] = $this->normalizeObject($object, $object->path);

            if ($recursive && $object->type == 'dir') {
                $listing = array_merge($listing, $this->listContents($object->path, $recursive));
            }
        }

        return $listing;
    }

    /**
     * Normalize a result from Copy
     *
     * @param   stdClass   $object
     * @param   string     $path
     * @return  array      file metadata
     */
    protected function normalizeObject($object, $path = null)
    {
        // validation
        if (is_a($object, 'stdClass') == false) {
            return false;
        }

        // build the dirname from the path
        $dirname = Util::dirname(Util::normalizePath($path));

        $result = compact('path', 'dirname');

        if (isset($object->modified_time)) {
            $result['timestamp'] = strtotime($object->modified_time);
        }

        return array_merge($result, Util::map((array)$object, static::$resultMap));
    }

    /**
     * Apply the path prefix
     *
     * @param   string  $path
     * @return  string  prefixed path
     */
    public function applyPathPrefix($path)
    {
        $prefixed = parent::applyPathPrefix($path);

        return '/' . ltrim($prefixed, '/');
    }
}
