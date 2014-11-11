<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

class ReplicateAdapter implements AdapterInterface
{
    /**
     * @var AdapterInterface $replica
     */
    protected $replica;

    /**
     * @var AdapterInterface $source
     */
    protected $source;

    /**
     * Constructor
     *
     * @param AdapterInterface $source
     * @param AdapterInterface $replica
     */
    public function __construct(AdapterInterface $source, AdapterInterface $replica)
    {
        $this->source = $source;
        $this->replica = $replica;
    }

    /**
     * Returns the replica adapter
     *
     * @return AdapterInterface
     */
    public function getReplicaAdapter()
    {
        return $this->replica;
    }

    /**
     * Returns the source adapter
     *
     * @return AdapterInterface
     */
    public function getSourceAdapter()
    {
        return $this->source;
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        if (! $this->source->write($path, $contents, $config)) {
            return false;
        }

        return $this->replica->write($path, $contents, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        if (! $this->source->writeStream($path, $resource, $config)) {
            return false;
        }

        return $this->replica->writeStream($path, $resource, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        if (! $this->source->update($path, $contents, $config)) {
            return false;
        }

        if ($this->replica->has($path)) {
            return $this->replica->update($path, $contents, $config);
        } else {
            return $this->replica->write($path, $contents, $config);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Config $config)
    {
        if (! $this->source->updateStream($path, $resource, $config)) {
            return false;
        }

        if ($this->replica->has($path)) {
            return $this->replica->updateStream($path, $resource, $config);
        } else {
            return $this->replica->writeStream($path, $resource, $config);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {
        if (! $this->source->rename($path, $newpath)) {
            return false;
        }

        return $this->replica->rename($path, $newpath);
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        if (! $this->source->copy($path, $newpath)) {
            return false;
        }

        return $this->replica->copy($path, $newpath);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        if (! $this->source->delete($path)) {
            return false;
        }

        if ($this->replica->has($path)) {
            return $this->replica->delete($path);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        if (! $this->source->deleteDir($dirname)) {
            return false;
        }

        return $this->replica->deleteDir($dirname);
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        if (! $this->source->createDir($dirname, $config)) {
            return false;
        }

        return $this->replica->createDir($dirname, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        return $this->source->has($path);
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        return $this->source->read($path);
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        return $this->source->readStream($path);
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        return $this->source->listContents($directory, $recursive);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        return $this->source->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        return $this->source->getSize($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        return $this->source->getMimetype($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        return $this->source->getTimestamp($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility($path)
    {
        return $this->source->getVisibility($path);
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility($path, $visibility)
    {
        if (! $this->source->setVisibility($path, $visibility)) {
            return false;
        }

        return $this->replica->setVisibility($path, $visibility);
    }
}
