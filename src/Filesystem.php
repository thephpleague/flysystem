<?php

namespace League\Flysystem;

use InvalidArgumentException;
use League\Flysystem\Plugin\PluggableTrait;
use League\Flysystem\Plugin\PluginNotFoundException;

/**
 * @method array getWithMetadata(string $path, array $metadata)
 * @method array listFiles(string $path = '', boolean $recursive = false)
 * @method array listPaths(string $path = '', boolean $recursive = false)
 * @method array listWith(array $keys = [], $directory = '', $recursive = false)
 */
class Filesystem implements FilesystemInterface
{
    use PluggableTrait;

    /**
     * @var  AdapterInterface  $adapter
     */
    protected $adapter;

    /**
     * @var  CacheInterface  $cache
     */
    protected $cache;

    /**
     * @var  Config  $config
     */
    protected $config;

    /**
     * Constructor
     *
     * @param AdapterInterface $adapter
     * @param CacheInterface   $cache
     * @param mixed            $config
     */
    public function __construct(AdapterInterface $adapter, CacheInterface $cache = null, $config = null)
    {
        $this->adapter = $adapter;
        $this->cache = $cache ?: new Cache\Memory();
        $this->cache->load();
        $this->config = Util::ensureConfig($config);
    }

    /**
     * Get the Adapter
     *
     * @return AdapterInterface adapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Get the Config
     *
     * @return Config config object
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get the Cache
     *
     * @return CacheInterface adapter
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        $path = Util::normalizePath($path);
        $exists = $this->cache->has($path);

        if (is_bool($exists)) {
            return $exists;
        }

        $result = $this->adapter->has($path);

        if (! $result) {
            $this->cache->storeMiss($path);

            return false;
        }

        $object = is_array($result) ? $result : compact('path');
        $this->cache->updateObject($path, $object, true);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, array $config = [])
    {
        $path = Util::normalizePath($path);
        $this->assertAbsent($path);
        $config = $this->prepareConfig($config);

        if (! $object = $this->adapter->write($path, $contents, $config)) {
            return false;
        }

        $this->cache->updateObject($path, $object + compact('contents'), true);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, array $config = [])
    {
        $path = Util::normalizePath($path);
        $this->assertAbsent($path);
        $config = $this->prepareConfig($config);

        if (! is_resource($resource)) {
            throw new InvalidArgumentException(__METHOD__.' expects argument #2 to be a valid resource.');
        }

        Util::rewindStream($resource);

        if (! $object = $this->adapter->writeStream($path, $resource, $config)) {
            return false;
        }

        $this->cache->updateObject($path, $object + ['contents' => false], true);

        return true;
    }

    /**
     * Create a file or update if exists
     *
     * @param string $path     path to file
     * @param string $contents file contents
     * @param mixed  $config
     *
     * @throws FileExistsException
     *
     * @return boolean success boolean
     */
    public function put($path, $contents, array $config = [])
    {
        $path = Util::normalizePath($path);

        if ($this->has($path)) {
            return $this->update($path, $contents, $config);
        }

        return $this->write($path, $contents, $config);
    }

    /**
     * Create a file or update if exists using a stream
     *
     * @param string   $path
     * @param resource $resource
     * @param mixed    $config
     *
     * @return boolean success boolean
     */
    public function putStream($path, $resource, array $config = [])
    {
        $path = Util::normalizePath($path);

        if ($this->has($path)) {
            return $this->updateStream($path, $resource, $config);
        }

        return $this->writeStream($path, $resource, $config);
    }

    /**
     * Read and delete a file.
     *
     * @param string $path
     *
     * @return string file contents
     *
     * @throws FileNotFoundException
     */
    public function readAndDelete($path)
    {
        $path = Util::normalizePath($path);
        $this->assertPresent($path);
        $contents = $this->read($path);

        if (! $contents) {
            return false;
        }

        $this->delete($path);

        return $contents;
    }

    /**
     * Update a file
     *
     * @param string $path     path to file
     * @param string $contents file contents
     * @param mixed  $config   Config object or visibility setting
     *
     * @throws FileNotFoundException
     *
     * @return boolean success boolean
     */
    public function update($path, $contents, array $config = [])
    {
        $path = Util::normalizePath($path);
        $config = $this->prepareConfig($config);

        $this->assertPresent($path);
        $object = $this->adapter->update($path, $contents, $config);

        if ($object === false) {
            return false;
        }

        $this->cache->updateObject($path, $object + compact('contents'), true);

        return true;
    }

    /**
     * Update a file with the contents of a stream
     *
     * @param string   $path
     * @param resource $resource
     * @param mixed    $config   Config object or visibility setting
     *
     * @return bool success boolean
     *
     * @throws InvalidArgumentException
     */
    public function updateStream($path, $resource, array $config = [])
    {
        if (! is_resource($resource)) {
            throw new InvalidArgumentException(__METHOD__.' expects argument #2 to be a valid resource.');
        }

        $path = Util::normalizePath($path);
        $config = $this->prepareConfig($config);
        $this->assertPresent($path);
        Util::rewindStream($resource);

        if (! $object = $this->adapter->updateStream($path, $resource, $config)) {
            return false;
        }

        $this->cache->updateObject($path, $object + ['contents' => false], true);

        return true;
    }

    /**
     * Read a file
     *
     * @param string $path path to file
     *
     * @throws FileNotFoundException
     *
     * @return string|false file contents or FALSE when fails
     *                      to read existing file
     */
    public function read($path)
    {
        $path = Util::normalizePath($path);
        $this->assertPresent($path);

        if ($contents = $this->cache->read($path)) {
            return $contents;
        }

        if (! ($object = $this->adapter->read($path))) {
            return false;
        }

        $this->cache->updateObject($path, $object, true);

        return $object['contents'];
    }

    /**
     * Retrieves a read-stream for a path
     *
     * @param string $path
     *
     * @return resource|false path resource or false when on failure
     */
    public function readStream($path)
    {
        $path = Util::normalizePath($path);
        $this->assertPresent($path);

        if ($stream = $this->cache->readStream($path)) {
            return $stream;
        }

        if (! $object = $this->adapter->readStream($path)) {
            return false;
        }

        $this->cache->updateObject($path, $object, true);

        return $object['stream'];
    }

    /**
     * Rename a file
     *
     * @param string $path    path to file
     * @param string $newpath new path
     *
     * @throws FileExistsException
     * @throws FileNotFoundException
     *
     * @return boolean success boolean
     */
    public function rename($path, $newpath)
    {
        $path = Util::normalizePath($path);
        $newpath = Util::normalizePath($newpath);
        $this->assertPresent($path);
        $this->assertAbsent($newpath);

        if ($this->adapter->rename($path, $newpath) === false) {
            return false;
        }

        $this->cache->rename($path, $newpath);

        return true;
    }

    /**
     * Copy a file
     *
     * @param string $path
     * @param string $newpath
     *
     * @return boolean
     */
    public function copy($path, $newpath)
    {
        $path = Util::normalizePath($path);
        $newpath = Util::normalizePath($newpath);
        $this->assertPresent($path);
        $this->assertAbsent($newpath);

        if ($this->adapter->copy($path, $newpath) === false) {
            return false;
        }

        $this->cache->copy($path, $newpath);

        return true;
    }

    /**
     * Delete a file
     *
     * @param string $path path to file
     *
     * @throws FileNotFoundException
     *
     * @return boolean success boolean
     */
    public function delete($path)
    {
        $path = Util::normalizePath($path);
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
     * @param string $dirname path to directory
     *
     * @return boolean success boolean
     */
    public function deleteDir($dirname)
    {
        $dirname = Util::normalizePath($dirname);

        if ($dirname === '') {
            throw new RootViolationException('Root directories can not be deleted.');
        }

        if ($this->adapter->deleteDir($dirname) === false) {
            return false;
        }

        $this->cache->deleteDir($dirname);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, array $config = [])
    {
        $dirname = Util::normalizePath($dirname);
        $config = $this->prepareConfig($config);
        $result  = $this->adapter->createDir($dirname, $config);

        if ($result === false) {
            return false;
        }

        $result['type'] = 'dir';
        $this->cache->updateObject($dirname, $result, true);

        return true;
    }

    /**
     * List the filesystem contents
     *
     * @param string  $directory
     * @param boolean $recursive
     *
     * @return array contents
     */
    public function listContents($directory = '', $recursive = false)
    {
        $directory = Util::normalizePath($directory);

        if ($this->cache->isComplete($directory, $recursive)) {
            return $this->cache->listContents($directory, $recursive);
        }

        $contents = $this->adapter->listContents($directory, $recursive);

        return $this->cache->storeContents($directory, $contents, $recursive);
    }

    /**
     * Get a file's mime-type
     *
     * @param string $path path to file
     *
     * @throws FileNotFoundException
     *
     * @return string|false file mime-type or FALSE when fails
     *                      to fetch mime-type from existing file
     */
    public function getMimetype($path)
    {
        $path = Util::normalizePath($path);
        $this->assertPresent($path);

        if ($mimetype = $this->cache->getMimetype($path)) {
            return $mimetype;
        }

        if (! $object = $this->adapter->getMimetype($path)) {
            return false;
        }

        $object = $this->cache->updateObject($path, $object, true);

        return $object['mimetype'];
    }

    /**
     * Get a file's timestamp
     *
     * @param string $path path to file
     *
     * @throws FileNotFoundException
     *
     * @return string|false timestamp or FALSE when fails
     *                      to fetch timestamp from existing file
     */
    public function getTimestamp($path)
    {
        $path = Util::normalizePath($path);
        $this->assertPresent($path);

        if ($timestamp = $this->cache->getTimestamp($path)) {
            return $timestamp;
        }

        if (! $object = $this->adapter->getTimestamp($path)) {
            return false;
        }

        $object = $this->cache->updateObject($path, $object, true);

        return $object['timestamp'];
    }

    /**
     * Get a file's visibility
     *
     * @param string $path path to file
     *
     * @return string|false visibility (public|private) or FALSE
     *                      when fails to check it in existing file
     */
    public function getVisibility($path)
    {
        $path = Util::normalizePath($path);
        $this->assertPresent($path);

        if ($visibility = $this->cache->getVisibility($path)) {
            return $visibility;
        }

        if (($object = $this->adapter->getVisibility($path)) === false) {
            return false;
        }

        $this->cache->updateObject($path, $object, true);

        return $object['visibility'];
    }

    /**
     * Get a file's size
     *
     * @param string $path path to file
     *
     * @return int|false file size or FALSE when fails
     *                   to check size of existing file
     */
    public function getSize($path)
    {
        $path = Util::normalizePath($path);
        $cached = $this->cache->getSize($path);

        if ($cached !== false) {
            return $cached;
        }

        if (($object = $this->adapter->getSize($path)) === false || !isset($object['size'])) {
            return false;
        }

        $this->cache->updateObject($path, $object, true);

        return (integer) $object['size'];
    }

    /**
     * Get a file's size
     *
     * @param string $path       path to file
     * @param string $visibility visibility
     *
     * @return boolean success boolean
     */
    public function setVisibility($path, $visibility)
    {
        $path = Util::normalizePath($path);

        if (! $object = $this->adapter->setVisibility($path, $visibility)) {
            return false;
        }

        if ($object === true) {
            $object = compact('visibility');
        }

        $this->cache->updateObject($path, $object, true);

        return true;
    }

    /**
     * Get a file's metadata
     *
     * @param string $path path to file
     *
     * @throws FileNotFoundException
     *
     * @return array|false file metadata or FALSE when fails
     *                     to fetch it from existing file
     */
    public function getMetadata($path)
    {
        $path = Util::normalizePath($path);
        $this->assertPresent($path);

        if ($metadata = $this->cache->getMetadata($path)) {
            return $metadata;
        }

        if (! $metadata = $this->adapter->getMetadata($path)) {
            return false;
        }

        return $this->cache->updateObject($path, $metadata, true);
    }

    /**
     * Get a file/directory handler
     *
     * @param string  $path
     * @param Handler $handler
     *
     * @return Handler file or directory handler
     */
    public function get($path, Handler $handler = null)
    {
        $path = Util::normalizePath($path);

        if (! $handler) {
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
     * @return $this
     */
    public function flushCache()
    {
        $this->cache->flush();

        return $this;
    }

    /**
     * Convert a config array to a Config object with the correct fallback
     *
     * @param array $config
     *
     * @return Config
     */
    protected function prepareConfig(array $config)
    {
        $config = new Config($config);
        $config->setFallback($this->config);

        return $config;
    }

    /**
     * Assert a file is present
     *
     * @param string $path path to file
     *
     * @throws FileNotFoundException
     */
    public function assertPresent($path)
    {
        if (! $this->has($path)) {
            throw new FileNotFoundException($path);
        }
    }

    /**
     * Assert a file is absent
     *
     * @param string $path path to file
     *
     * @throws FileExistsException
     */
    public function assertAbsent($path)
    {
        if ($this->has($path)) {
            throw new FileExistsException($path);
        }
    }

    /**
     * Plugins pass-through
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($method, array $arguments)
    {
        try {
            return $this->invokePlugin($method, $arguments, $this);
        } catch (PluginNotFoundException $e) {
            throw new \BadMethodCallException(
                'Call to undefined method '
                .__CLASS__
                .'::'.$method);
        }
    }
}
