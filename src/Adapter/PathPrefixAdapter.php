<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Adapter\Polyfill\PathPrefixTrait;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

/**
 * Class PrefixAdapter
 * Decorator to add support for path prefixes to any adapter.
 */
class PathPrefixAdapter implements AdapterInterface
{
    use PathPrefixTrait;

    /**
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * PathPrefixAdapter constructor.
     * @param AdapterInterface $adapter The adapter to decorate
     * @param string $prefix The path prefix to use
     */
    public function __construct(AdapterInterface $adapter, $prefix)
    {
        $this->adapter = $adapter;
        $this->setPathPrefix($prefix);
    }

    /**
     * @inheritdoc
     */
    public function write($path, $contents, Config $config)
    {
        return $this->adapter->write($this->applyPathPrefix($path), $contents, $config);
    }

    /**
     * @inheritdoc
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->adapter->writeStream($this->applyPathPrefix($path), $resource, $config);
    }

    /**
     * @inheritdoc
     */
    public function update($path, $contents, Config $config)
    {
        $this->adapter->update($this->applyPathPrefix($path), $contents, $config);
    }

    /**
     * @inheritdoc
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->adapter->updateStream($this->applyPathPrefix($path), $resource, $config);
    }

    /**
     * @inheritdoc
     */
    public function rename($path, $newpath)
    {
        return $this->adapter->rename($this->applyPathPrefix($path), $this->applyPathPrefix($newpath));
    }

    /**
     * @inheritdoc
     */
    public function copy($path, $newpath)
    {
        return $this->adapter->copy($this->applyPathPrefix($path), $this->applyPathPrefix($newpath));
    }

    /**
     * @inheritdoc
     */
    public function delete($path)
    {
        return $this->adapter->delete($this->applyPathPrefix($path));
    }

    /**
     * @inheritdoc
     */
    public function deleteDir($dirname)
    {
        return $this->adapter->delete($this->applyPathPrefix($dirname));
    }

    /**
     * @inheritdoc
     */
    public function createDir($dirname, Config $config)
    {
        return $this->adapter->createDir($this->applyPathPrefix($dirname), $config);
    }

    /**
     * @inheritdoc
     */
    public function setVisibility($path, $visibility)
    {
        return $this->adapter->setVisibility($this->applyPathPrefix($path), $visibility);
    }

    /**
     * @inheritdoc
     */
    public function has($path)
    {
        return $this->adapter->has($this->applyPathPrefix($path));
    }

    /**
     * @inheritdoc
     */
    public function read($path)
    {
        return $this->adapter->read($this->applyPathPrefix($path));
    }

    /**
     * @inheritdoc
     */
    public function readStream($path)
    {
        return $this->adapter->readStream($this->applyPathPrefix($path));
    }

    /**
     * @inheritdoc
     */
    public function listContents($directory = '', $recursive = false)
    {
        return $this->adapter->listContents($this->applyPathPrefix($directory));
    }

    /**
     * @inheritdoc
     */
    public function getMetadata($path)
    {
        return $this->adapter->getMetadata($this->applyPathPrefix($path));
    }

    /**
     * @inheritdoc
     */
    public function getSize($path)
    {
        return $this->adapter->getSize($this->applyPathPrefix($path));
    }

    /**
     * @inheritdoc
     */
    public function getMimetype($path)
    {
        return $this->adapter->getMimetype($this->applyPathPrefix($path));
    }

    /**
     * @inheritdoc
     */
    public function getTimestamp($path)
    {
        return $this->adapter->getTimestamp($this->applyPathPrefix($path));
    }

    /**
     * @inheritdoc
     */
    public function getVisibility($path)
    {
        return $this->adapter->getVisibility($this->applyPathPrefix($path));
    }
}
