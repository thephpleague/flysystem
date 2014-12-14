<?php

namespace League\Flysystem\Adapter;

use Guzzle\Http\Exception\ClientErrorResponseException;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Adapter\Polyfill\StreamedCopyTrait;
use League\Flysystem\Config;
use League\Flysystem\Util;
use OpenCloud\ObjectStore\Exception\ObjectNotFoundException;
use OpenCloud\ObjectStore\Resource\Container;
use OpenCloud\ObjectStore\Resource\DataObject;

class Rackspace extends AbstractAdapter
{
    use StreamedCopyTrait;
    use NotSupportingVisibilityTrait;

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
     * @param Container $container
     * @param string    $prefix
     */
    public function __construct(Container $container, $prefix = null)
    {
        $this->setPathPrefix($prefix);

        $this->container = $container;
    }

    /**
     * Get an object
     *
     * @param string $path
     *
     * @return DataObject
     */
    protected function getObject($path)
    {
        $location = $this->applyPathPrefix($path);

        return $this->container->getObject($location);
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
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
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        $object = $this->getObject($path);
        $object->setContent($contents);
        $object->setEtag(null);
        $response = $object->update();

        if (! $response->getLastModified()) {
            return false;
        }

        return $this->normalizeObject($response);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        $paths = [];
        $prefix = '/'.$this->container->getName().'/';
        $location = $this->applyPathPrefix($dirname);
        $objects = $this->container->objectList(['prefix' => $location]);

        foreach ($objects as $object) {
            $paths[] = $prefix.ltrim($object->getName(), '/');
        }

        $service = $this->container->getService();
        $response =  $service->bulkDelete($paths);

        if ($response->getStatusCode() === 200) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        return ['path' => $dirname];
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
    public function updateStream($path, $resource, Config $config)
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
        } catch (ClientErrorResponseException $e) {
            return false;
        } catch (ObjectNotFoundException $e) {
            return false;
        }

        return $this->normalizeObject($object);
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        $object = $this->getObject($path);
        $data = $this->normalizeObject($object);
        $data['contents'] = (string) $object->getContent();

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        $object = $this->getObject($path);
        $data = $this->normalizeObject($object);
        $responseBody = $object->getContent();
        $data['stream'] = $responseBody->getStream();
        $responseBody->detachStream();

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        $location = $this->applyPathPrefix($directory);
        $response = $this->container->objectList(['prefix' => $location]);
        $response = iterator_to_array($response);
        $contents = array_map([$this, 'normalizeObject'], $response);

        return Util::emulateDirectories($contents);
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeObject(DataObject $object)
    {
        $name = $object->getName();
        $name = $this->removePathPrefix($name);
        $mimetype = explode('; ', $object->getContentType());

        return [
            'type' => 'file',
            'dirname' => Util::dirname($name),
            'path' => $name,
            'timestamp' => strtotime($object->getLastModified()),
            'mimetype' => reset($mimetype),
            'size' => $object->getContentLength(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        $object = $this->getObject($path);

        return $this->normalizeObject($object);
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
}
