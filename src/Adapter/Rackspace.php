<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Util;
use OpenCloud\ObjectStore\Resource\Container;
use OpenCloud\ObjectStore\Resource\DataObject;
use OpenCloud\ObjectStore\Exception\ObjectNotFoundException;
use Guzzle\Http\Exception\ClientErrorResponseException;

class Rackspace extends AbstractAdapter
{
    /**
     * @var  Container  $container
     */
    protected $container;

    /**
     * Constructor
     *
     * @param  Container  $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get an object
     *
     * @param   string  $path
     * @return  DataObject
     */
    protected function getObject($path)
    {
        return $this->container->getObject($path);
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
        $response = $this->container->uploadObject($path, $contents);

        return $this->normalizeObject($response);
    }

    /**
     * Update a file
     *
     * @param   string  $path
     * @param   string  $contents
     * @return  array   file metadata
     */
    public function update($path, $contents)
    {
        $object = $this->getObject($path);
        $object->setContent($contents);
        $object->setEtag(null);
        $response = $object->update();

        if ( ! $response->getLastModified()) {
            return false;
        }

        return $this->normalizeObject($response);
    }

    /**
     * Rename a file
     *
     * @param   string      $path
     * @param   string      $newpath
     * @return  bool|array  false or file metadata
     */
    public function rename($path, $newpath)
    {
        $object = $this->getObject($path);
        $destination = '/'.$this->container->getName().'/'.ltrim($newpath, '/');
        $response = $object->copy($destination);

        if ($response->getStatusCode() !== 201) {
            return false;
        }

        $object->delete();

        return true;
    }

    /**
     * Delete a file
     *
     * @param   string  $path
     * @return  boolean
     */
    public function delete($path)
    {
        $object = $this->getObject($path);
        $response = $object->delete();

        if ($response->getStatusCode() !== 204) {
            return false;
        }

        return true;
    }

    /**
     * Delete a directory
     *
     * @param   string  $dirname
     * @return  boolean
     */
    public function deleteDir($dirname)
    {
        $paths = array();
        $prefix = '/'.$this->container->getName().'/';
        $objects = $this->container->objectList(array('prefix' => $dirname));

        foreach ($objects as $object)
            $paths[] = $prefix.ltrim($object->getName(), '/');

        $service = $this->container->getService();
        $response =  $service->bulkDelete($paths);

        if ($response->getStatusCode() === 200) {
            return true;
        }

        return false;
    }

    /**
     * Create a directory
     *
     * @param   string  $dirname
     * @return  array
     */
    public function createDir($dirname)
    {
        return array('path' => $dirname);
    }

    public function writeStream($path, $resource, $config = null)
    {
        return $this->write($path, $resource, $config);
    }

    public function updateStream($path, $resource)
    {
        return $this->update($path, $resource);
    }

    public function has($path)
    {
        try {
            $object = $this->getObject($path);
        } catch(ClientErrorResponseException $e) {
            return false;
        } catch(ObjectNotFoundException $e) {
            return false;
        }

        return $this->normalizeObject($object);
    }

    /**
     * Get a file's contents
     *
     * @param   string  $path
     * @return  array   file metadata
     */
    public function read($path)
    {
        $object = $this->getObject($path);
        $data = $this->normalizeObject($object);
        $data['contents'] = (string) $object->getContent();

        return $data;
    }

    /**
     * Get a file's metadata
     *
     * @param   string  $path
     * @return  array   file metadata
     */
    public function listContents($directory = '', $recursive = false)
    {
        $response = $this->container->objectList(array('prefix' => $directory));
        $response = iterator_to_array($response);
        $contents = array_map(array($this, 'normalizeObject'), $response);

        return Util::emulateDirectories($contents);
    }

    /**
     * Normalize a DataObject
     *
     * @param   DataObject  $object
     * @return  array       file metadata
     */
    protected function normalizeObject(DataObject $object)
    {
        $name = $object->getName();
        $mimetype = explode('; ', $object->getContentType());

        return array(
            'type' => 'file',
            'dirname' => Util::dirname($name),
            'path' => $name,
            'timestamp' => strtotime($object->getLastModified()),
            'mimetype' => reset($mimetype),
            'size' => $object->getContentLength(),
        );
    }

    /**
     * Get a file's metadata
     *
     * @param   string  $path
     * @return  array   file metadata
     */
    public function getMetadata($path)
    {
        $object = $this->getObject($path);

        return $this->normalizeObject($object);
    }

    /**
     * Get a file's size
     *
     * @param   string  $path
     * @return  array   file metadata
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get a file's mimetype
     *
     * @param   string  $path
     * @return  array   file metadata
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get a file's timestamp
     *
     * @param   string  $path
     * @return  array   file metadata
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }
}
