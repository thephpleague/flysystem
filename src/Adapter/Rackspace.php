<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Config;
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
     * @var  string  $prefix
     */
    protected $prefix;

    /**
     * Constructor
     *
     * @param  Container  $container
     * @param  string     $prefix
     */
    public function __construct(Container $container, $prefix = null)
    {
        $this->setPathPrefix($prefix);

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
        $location = $this->applyPathPrefix($path);

        return $this->container->getObject($location);
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
        $location = $this->applyPathPrefix($path);
        $headers = [];

        if ($config && $config->has('headers')) {
            $headers =  $config->get('headers');
        }

        $response = $this->container->uploadObject($location, $contents, $headers);

        return $this->normalizeObject($response);
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
        $newlocation = $this->applyPathPrefix($newpath);
        $destination = '/'.$this->container->getName().'/'.ltrim($newlocation, '/');
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
        try {
            $object = $this->getObject($path);
        } catch (ObjectNotFoundException $exception) {
            return false;
        }

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
        $location = $this->applyPathPrefix($dirname);
        $objects = $this->container->objectList(array('prefix' => $location));

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
     * @param   string       $dirname directory name
     * @param   array|Config $options
     *
     * @return  bool
     */
    public function createDir($dirname, $options = null)
    {
        return array('path' => $dirname);
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, $config = null)
    {
        return $this->write($path, $resource, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, $config = null)
    {
        return $this->update($path, $resource, $config);
    }

    /**
     * {@inheritdoc}
     */
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
     * @param string $directory
     * @param bool   $recursive
     * @return  array   file metadata
     */
    public function listContents($directory = '', $recursive = false)
    {
        $location = $this->applyPathPrefix($directory);
        $response = $this->container->objectList(array('prefix' => $location));
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
        $name = $this->removePathPrefix($name);

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
