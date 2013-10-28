<?php

namespace Flysystem;

class Filesystem
{
    /**
     * @var  AdapterInterface  $adapter
     */
    protected $adapter;

    /**
     * @var  CacheInterface  $cache
     */
    protected $cache;

    /**
     * @var  string  $visibility
     */
    protected $visibility;

    /**
     * Constructor
     *
     * @param AdapterInterface $adapter
     * @param CacheInterface   $cache
     */
    public function __construct(AdapterInterface $adapter, CacheInterface $cache = null, $visibility = AdapterInterface::VISIBILITY_PUBLIC)
    {
        $this->adapter = $adapter;
        $this->cache = $cache ?: new Cache\Memory;
        $this->cache->load();
        $this->visibility = $visibility;
    }

    public function getAdapter()
    {
        return $this->adapter;
    }

    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Check wether a path exists
     *
     * @param  string  $path path to check
     * @return boolean wether the path exists
     */
    public function has($path)
    {
        if ($this->cache->has($path)) {
            return true;
        }

        if ($this->cache->isComplete() or ($data = $this->adapter->has($path)) === false) {
            return false;
        }

        $this->cache->updateObject($path, $data === true ? [] : $data, true);

        return true;
    }

    /**
     * Write a file
     *
     * @param  string              $path     path to file
     * @param  string              $contents file contents
     * @throws FileExistsException
     * @return boolean             success boolean
     */
    public function write($path, $contents, $visibility = null)
    {
        $this->assertAbsent($path);

        if ( ! $data = $this->adapter->write($path, $contents, $visibility ?: $this->visibility)) {
            return false;
        }

        $this->cache->updateObject($path, $data, true);
        $this->cache->ensureParentDirectories($path);

        return true;
    }

    /**
     * Update a file
     *
     * @param  string                $path     path to file
     * @param  string                $contents file contents
     * @throws FileNotFoundException
     * @return boolean               success boolean
     */
    public function update($path, $contents)
    {
        $this->assertPresent($path);
        $data = $this->adapter->update($path, $contents);

        if ($data === false) {
            return false;
        }

        $this->cache->updateObject($path, $data, true);

        return true;
    }

    /**
     * Write a file
     *
     * @param  string                $path path to file
     * @throws FileNotFoundException
     * @return string                file contents
     */
    public function read($path)
    {
        $this->assertPresent($path);

        if ($contents = $this->cache->read($path)) {
            return $contents;
        }

        if ( ! $data = $this->adapter->read($path)) {
            return false;
        }

        $this->cache->updateObject($path, $data, true);

        return $data['contents'];
    }

    /**
     * Write a file
     *
     * @param  string                $path    path to file
     * @param  string                $newpath new path
     * @throws FileExistsException
     * @throws FileNotFoundException
     * @return boolean               success boolean
     */
    public function rename($path, $newpath)
    {
        $this->assertPresent($path);
        $this->assertAbsent($newpath);

        if ($this->adapter->rename($path, $newpath) === false) {
            return false;
        }

        $this->cache->rename($path, $newpath);

        return true;
    }

    /**
     * Delete a file
     *
     * @param  string                $path path to file
     * @throws FileNotFoundException
     * @return boolean               success boolean
     */
    public function delete($path)
    {
        $this->assertPresent($path);

        if ($this->adapter->delete($path) === false) {
            return false;
        }

        $this->cache->delete($path);

        return true;
    }

    /**
     * Delete a directory
     *
     * @param  string  $path path to directory
     * @return boolean success boolean
     */
    public function deleteDir($dirname)
    {
        if ($this->adapter->deleteDir($dirname) === false) {
            return false;
        }

        $this->cache->deleteDir($dirname);

        return true;
    }

    public function createDir($dirname)
    {
        $data = $this->adapter->createDir($dirname);

        $this->cache->updateObject($dirname, $data, true);
    }

    /**
     * List the filesystem contents
     *
     * @return array contents
     */
    public function listContents()
    {
        if ($this->cache->isComplete()) {
            return $this->cache->listContents();
        }

        $contents = $this->adapter->listContents();

        return $this->cache->storeContents($contents);
    }

    /**
     * Get a file's mimetype
     *
     * @param  string                $path path to file
     * @throws FileNotFoundException
     * @return string                file mimetype
     */
    public function getMimetype($path)
    {
        $this->assertPresent($path);

        if ($mimetype = $this->cache->getMimetype($path)) {
            return $mimetype;
        }

        if ( ! $data = $this->adapter->getMimetype($path)) {
            return false;
        }

        $data = $this->cache->updateObject($path, $data, true);

        return $data['mimetype'];
    }

     /**
     * Get a file's timestamp
     *
     * @param  string                $path path to file
     * @throws FileNotFoundException
     * @return string                file mimetype
     */
    public function getTimestamp($path)
    {
        $this->assertPresent($path);

        if ($mimetype = $this->cache->getTimestamp($path)) {
            return $mimetype;
        }

        if ( ! $data = $this->adapter->getTimestamp($path)) {
            return false;
        }

        $data = $this->cache->updateObject($path, $data, true);

        return $data['timestamp'];
    }

    public function getVisibility($path)
    {
        $this->assertPresent($path);

        if ($visibility = $this->cache->getVisibility($path)) {
            return $visibility;
        }

        if (($data = $this->adapter->getVisibility($path)) === false) {
            return false;
        }

        $this->cache->updateObject($path, $data, true);

        return $data['visibility'];
    }

    public function getSize($path)
    {
        if ($visibility = $this->cache->getSize($path)) {
            return $visibility;
        }

        if (($data = $this->adapter->getSize($path)) === false) {
            return false;
        }

        $this->cache->updateObject($path, $data, true);

        return $data['size'];
    }

    public function setVisibility($path, $visibility)
    {
        if ( ! $data = $this->adapter->setVisibility($path, $visibility)) {
            return false;
        }

        $this->cache->updateObject($path, $data, true);

        return $data['visibility'];
    }

    /**
     * Get a file's metadata
     *
     * @param  string                $path path to file
     * @throws FileNotFoundException
     * @return array                 file metadata
     */
    public function getMetadata($path)
    {
        $this->assertPresent($path);

        if ($metadata = $this->cache->getMetadata($path)) {
            return $metadata;
        }

        if ( ! $metadata = $this->adapter->getMetadata($path)) {
            return false;
        }

        return $this->cache->updateObject($path, $metadata, true);
    }

    public function flushCache()
    {
        $this->cache->flush();

        return $this;
    }

    /**
     * Assert a file is present
     *
     * @param  string                $path path to file
     * @throws FileNotFoundException
     */
    protected function assertPresent($path)
    {
        if ( ! $this->has($path)) {
            throw new FileNotFoundException($path);
        }
    }

    /**
     * Assert a file is absent
     *
     * @param  string              $path path to file
     * @throws FileExistsException
     */
    protected function assertAbsent($path)
    {
        if ($this->has($path)) {
            throw new FileExistsException($path);
        }
    }
}
