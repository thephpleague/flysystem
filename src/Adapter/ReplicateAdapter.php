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
     * Write a new file to the source and replica
     *
     * @param   string $path
     * @param   string $contents
     * @param   mixed  $config Config object or visibility setting
     *
     * @return  false|array  false on failure file meta data on success
     */
    public function write($path, $contents, $config = null)
    {
        if ( ! $this->source->write($path, $contents, $config)) {
            return false;
        }

        return $this->replica->write($path, $contents, $config);
    }

    /**
     * Write a new file to the source and replica from a stream
     *
     * @param   string $path
     * @param   resource $resource
     * @param   mixed  $config Config object or visibility setting
     *
     * @return  false|array  false on failure file meta data on success
     */
    public function writeStream($path, $resource, $config = null)
    {
        if ( ! $this->source->writeStream($path, $resource, $config)) {
            return false;
        }

        return $this->replica->writeStream($path, $resource, $config);
    }

    /**
     * Update a file on the source and replica
     *
     * @param   string $path
     * @param   string $contents
     * @param   mixed  $config Config object or visibility setting
     *
     * @return  false|array  false on failure file meta data on success
     */
    public function update($path, $contents, $config = null)
    {
        if ( ! $this->source->update($path, $contents, $config)) {
            return false;
        }

        if ($this->replica->has($path)) {
            return $this->replica->update($path, $contents, $config);
        } else {
            return $this->replica->write($path, $contents, $config);
        }
    }

    /**
     * Update a file on the source and replica
     *
     * @param   string $path
     * @param   resource $resource
     * @param   mixed  $config Config object or visibility setting
     *
     * @return  false|array  false on failure file meta data on success
     */
    public function updateStream($path, $resource, $config = null)
    {
        if ( ! $this->source->updateStream($path, $resource, $config)) {
            return false;
        }

        if ($this->replica->has($path)) {
            return $this->replica->updateStream($path, $resource, $config);
        } else {
            return $this->replica->writeStream($path, $resource, $config);
        }
    }

    /**
     * Rename a file on the source and replica
     *
     * @param   string $path
     * @param   string $newpath
     *
     * @return  boolean
     */
    public function rename($path, $newpath)
    {
        if ( ! $this->source->rename($path, $newpath)) {
            return false;
        }

        return $this->replica->rename($path, $newpath);
    }

    /**
     * Copy a file
     *
     * @param   string  $path
     * @param   string  $newpath
     * @return  boolean
     */
    public function copy($path, $newpath)
    {
        if ( ! $this->source->copy($path, $newpath)) {
            return false;
        }

        return $this->replica->copy($path, $newpath);
    }

    /**
     * Delete a file on the source and replica
     *
     * @param   string $path
     *
     * @return  boolean
     */
    public function delete($path)
    {
        if ( ! $this->source->delete($path)) {
            return false;
        }

        if ($this->replica->has($path)) {
            return $this->replica->delete($path);
        }

        return true;
    }

    /**
     * Delete a directory on the source and replica
     *
     * @param   string $dirname
     *
     * @return  boolean
     */
    public function deleteDir($dirname)
    {
        if ( ! $this->source->deleteDir($dirname)) {
            return false;
        }

        return $this->replica->deleteDir($dirname);
    }

    /**
     * Create a directory on the source and replica
     *
     * @param   string       $dirname directory name
     * @param   array|Config $options
     *
     * @return  bool
     */
    public function createDir($dirname, $options = null)
    {
        if ( ! $this->source->createDir($dirname, $options)) {
            return false;
        }

        return $this->replica->createDir($dirname, $options);
    }

    /**
     * Check whether a file exists in the source
     *
     * @param   string $path
     *
     * @return  bool
     */
    public function has($path)
    {
        return $this->source->has($path);
    }

    /**
     * Read a file from the source
     *
     * @param   string $path
     *
     * @return  false|array
     */
    public function read($path)
    {
        return $this->source->read($path);
    }

    /**
     * Get a read stream from the source
     *
     * @param   string $path
     *
     * @return  false|array
     */
    public function readStream($path)
    {
        return $this->source->readStream($path);
    }

    /**
     * List contents of a directory from the source
     *
     * @param   string $directory
     * @param   bool   $recursive
     *
     * @return  array
     */
    public function listContents($directory = '', $recursive = false)
    {
        return $this->source->listContents($directory, $recursive);
    }

    /**
     * Get all the meta data of a file or directory from the source
     *
     * @param   string $path
     *
     * @return  false|array
     */
    public function getMetadata($path)
    {
        return $this->source->getMetadata($path);
    }

    /**
     * Get all the size of a file or directory from the source
     *
     * @param   string $path
     *
     * @return  false|array
     */
    public function getSize($path)
    {
        return $this->source->getSize($path);
    }

    /**
     * Get the mimetype of a file from the source
     *
     * @param   string $path
     *
     * @return  false|array
     */
    public function getMimetype($path)
    {
        return $this->source->getMimetype($path);
    }

    /**
     * Get the timestamp of a file from the source
     *
     * @param   string $path
     *
     * @return  false|array
     */
    public function getTimestamp($path)
    {
        return $this->source->getTimestamp($path);
    }

    /**
     * Get the visibility of a file
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getVisibility($path)
    {
        return $this->source->getVisibility($path);
    }

    /**
     * Set the file visibility
     *
     * @param   string  $path
     * @param   string  $visibility
     * @return  false|array
     */
    public function setVisibility($path, $visibility)
    {
        if ( ! $this->source->setVisibility($path, $visibility)) {
            return false;
        }

        return $this->replica->setVisibility($path, $visibility);
    }
}
