<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Util;
use OpenCloud\ObjectStore\Resource\Container;
use OpenCloud\ObjectStore\Resource\DataObject;
use Guzzle\Http\Exception\ClientErrorResponseException;

class RackspaceCloudFiles extends AbstractAdapter
{
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    protected function getObject($path)
    {
        return $this->container->getObject($path);
    }

    public function write($path, $contents, $config = null)
    {
        $response = $this->container->uploadObject($path, $contents);

        return $this->normalizeObject($response);
    }

    public function update($path, $contents)
    {
        $object = $this->getObject($path);
        $object->setContent($contents);

        return $object->save();
    }

    public function rename($path, $newpath)
    {
        $object = $this->getObject($path);

        if ($result = $object->copy($newpath)) {
            $this->delete($path);

            return $result;
        }
    }

    public function delete($path)
    {
        $object = $this->getObject($path);

        return $object->delete();
    }

    public function deleteDir($dirname)
    {
        $paths = array();
        $objects = $this->container->objectList(array('prefix' => $dirname));

        foreach ($objects as $object)
            $paths[] = $object->getName();

        // $this->container->getClient()->bulk
    }

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
        }

        return $this->normalizeObject($object);
    }

    public function read($path)
    {
        $object = $this->getObject($path);

        return array('contents' => (string) $object->getContent());
    }

    public function listContents($directory = '', $recursive = false)
    {
        $response = $this->container->objectList(array('prefix' => $directory));
        $response = iterator_to_array($response);
        $contents = array_map(array($this, 'normalizeObject'), $response);

        return Util::emulateDirectories($contents);
    }

    public function normalizeObject(DataObject $object)
    {
        $mimetype = explode('; ', $object->getContentType());

        return array(
            'type' => 'file',
            'dirname' => Util::dirname($object->getName()),
            'path' => $object->getName(),
            'timestamp' => strtotime($object->getLastModified()),
            'mimetype' => reset($mimetype),
            'size' => $object->getContentLength(),
        );
    }

    public function getMetadata($path)
    {
        $object = $this->getObject($path);

        return $this->normalizeObject($path);
    }

    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }
}