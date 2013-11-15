<?php

namespace Flysystem;

use LogicException;
use InvalidArgumentException;

class Filesystem implements FilesystemInterface
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
     * @var  array  $plugins
     */
    protected $plugins = array();

    /**
     * Constructor
     *
     * @param AdapterInterface $adapter
     * @param CacheInterface   $cache
     * @param string           $visibility
     */
    public function __construct(AdapterInterface $adapter, CacheInterface $cache = null, $visibility = AdapterInterface::VISIBILITY_PUBLIC)
    {
        $this->adapter = $adapter;
        $this->cache = $cache ?: new Cache\Memory;
        $this->cache->load();
        $this->visibility = $visibility;
    }

    /**
     * Get the Adapter
     *
     * @return  AdapterInterface  adapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Get the Cache
     *
     * @return  CacheInterface  adapter
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Check whether a path exists
     *
     * @param  string  $path path to check
     * @return boolean wether the path exists
     */
    public function has($path)
    {
        if ($this->cache->has($path)) {
            return true;
        }

        if ($this->cache->isComplete(Util::dirname($path), false) or ($data = $this->adapter->has($path)) === false) {
            return false;
        }

        $this->cache->updateObject($path, $data === true ? array() : $data, true);

        return true;
    }

    /**
     * Write a file
     *
     * @param  string              $path     path to file
     * @param  string              $contents file contents
     * @param  string              $visibility
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
     * Create a file or update if exists
     *
     * @param  string              $path     path to file
     * @param  string              $contents file contents
     * @param  string              $visibility
     * @throws FileExistsException
     * @return boolean             success boolean
     */
    public function put($path, $contents, $visibility = null)
    {
        if ($this->has($path)) {
            if (($data = $this->adapter->update($path, $contents)) === false) {
                return false;
            }

            $this->cache->updateObject($path, $data, true);
        } else {
            if ( ! $data = $this->adapter->write($path, $contents, $visibility ?: $this->visibility)) {
                return false;
            }

            $this->cache->updateObject($path, $data, true);
            $this->cache->ensureParentDirectories($path);
        }

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
     * Read a file
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
     * Rename a file
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
     * @param  string  $dirname path to directory
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

    /**
     * Create a directory
     *
     * @param   string  $dirname  directory name
     * @return  void
     */
    public function createDir($dirname)
    {
        $data = $this->adapter->createDir($dirname);

        $this->cache->updateObject($dirname, $data, true);
    }

    /**
     * List the filesystem contents
     *
     * @param  string   $directory
     * @param  boolean  $recursive
     * @return array    contents
     */
    public function listContents($directory = '', $recursive = false)
    {
        if ($this->cache->isComplete($directory, $recursive)) {
            return $this->cache->listContents($directory, $recursive);
        }

        $contents = $this->adapter->listContents($directory, $recursive);

        return $this->cache->storeContents($directory, $contents, $recursive);
    }

    /**
     * List all paths
     *
     * @return  array  paths
     */
    public function listPaths()
    {
        $result = array();
        $contents = $this->listContents();

        foreach ($contents as $object) {
            $result[] = $object['path'];
        }

        return $result;
    }

    /**
     * List contents with metadata
     *
     * @param   ...string|array  $key  metadata key
     * @return  array            listing with metadata
     */
    public function listWith($key)
    {
        $keys = is_array($key) ? $key : func_get_args();
        $contents = $this->listContents();

        foreach ($contents as $index => $object) {
            if ($object['type'] === 'file') {
                $contents[$index] = array_merge($object, $this->getWithMetadata($object['path'], $keys));
            }
        }

        return $contents;
    }

    /**
     * Get metadata for an object with required metadata
     *
     * @param   string  $path      path to file
     * @param   array   $metadata  metadata keys
     * @throws InvalidArgumentException
     * @return  array   metadata
     */
    public function getWithMetadata($path, array $metadata)
    {
        $object = $this->getMetadata($path);

        foreach ($metadata as $key) {
            if ( ! method_exists($this, $method = 'get'.ucfirst($key))) {
                throw new InvalidArgumentException('Could not fetch metadata: '.$key);
            }

            $object[$key] = $this->{$method}($path);
        }

        return $object;
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

    /**
     * Get a file's visibility
     *
     * @param   string  $path  path to file
     * @return  string  visibility (public|private)
     */
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

    /**
     * Get a file's size
     *
     * @param   string  $path  path to file
     * @return  int     file size
     */
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

    /**
     * Get a file's size
     *
     * @param   string   $path        path to file
     * @param   string   $visibility  visibility
     * @return  boolean  success boolean
     */
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

    /**
     * Get a file/directory handler
     *
     * @param   string   $path
     * @param   Handler  $handler
     * @return  Handler  file or directory handler
     */
    public function get($path, Handler $handler = null)
    {
        if ( ! $handler) {
            $metadata = $this->getMetadata($path);

            $handler = $metadata['type'] === 'file' ? new File($this, $path) : new Directory($this, $path);
        }

        $handler->setPath($path);
        $handler->setFilesystem($this);

        return $handler;
    }

    /**
     * Flush the cache
     *
     * @return  $this
     */
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

    /**
     * Register a plugin
     *
     * @param   PluginInterface  $plugin
     * @return  $this
     */
    public function addPlugin(PluginInterface $plugin)
    {
        $plugin->setFilesystem($this);
        $method = $plugin->getMethod();

        $this->plugins[$method] = $plugin;

        return $this;
    }

    /**
     * Register a plugin
     *
     * @param   string           $method
     * @return  PluginInterface  $plugin
     * @throws  LogicException
     */
    public function findPlugin($method)
    {
        if ( ! isset($this->plugins[$method])) {
            throw new LogicException('Plugin not found for method: '.$method);
        }

        return $this->plugins[$method];
    }

    /**
     * Plugins passthrough
     *
     * @param   string  $method
     * @param   array   $arguments
     * @return  mixed
     */
    public function __call($method, array $arguments)
    {
        $plugin = $this->findPlugin($method);
        $callback = array($plugin, 'handle');

        return call_user_func_array($callback, $arguments);
    }
}
