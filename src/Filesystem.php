<?php

namespace League\Flysystem;

use BadMethodCallException;
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
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param AdapterInterface $adapter
     * @param Config|array     $config
     */
    public function __construct(AdapterInterface $adapter, $config = null)
    {
        $this->adapter = $adapter;
        $this->config = Util::ensureConfig($config);
    }

    /**
     * Get the Adapter.
     *
     * @return AdapterInterface adapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Get the Config.
     *
     * @return Config config object
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        $path = Util::normalizePath($path);

        return (bool) $this->adapter->has($path);
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, array $config = [])
    {
        $path = Util::normalizePath($path);
        $this->assertAbsent($path);
        $config = $this->prepareConfig($config);

        return (bool) $this->adapter->write($path, $contents, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, array $config = [])
    {
        if (! is_resource($resource)) {
            throw new InvalidArgumentException(__METHOD__.' expects argument #2 to be a valid resource.');
        }

        $path = Util::normalizePath($path);
        $this->assertAbsent($path);
        $config = $this->prepareConfig($config);

        Util::rewindStream($resource);

        return (bool) $this->adapter->writeStream($path, $resource, $config);
    }

    /**
     * Create a file or update if exists.
     *
     * @param string $path     path to file
     * @param string $contents file contents
     * @param mixed  $config
     *
     * @throws FileExistsException
     *
     * @return bool success boolean
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
     * Create a file or update if exists using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param mixed    $config
     *
     * @return bool success boolean
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
     * @throws FileNotFoundException
     *
     * @return string file contents
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
     * Update a file.
     *
     * @param string $path     path to file
     * @param string $contents file contents
     * @param mixed  $config   Config object or visibility setting
     *
     * @throws FileNotFoundException
     *
     * @return bool success boolean
     */
    public function update($path, $contents, array $config = [])
    {
        $path = Util::normalizePath($path);
        $config = $this->prepareConfig($config);

        $this->assertPresent($path);

        return (bool) $this->adapter->update($path, $contents, $config);
    }

    /**
     * Update a file with the contents of a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param mixed    $config   Config object or visibility setting
     *
     * @throws InvalidArgumentException
     *
     * @return bool success boolean
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

        return (bool) $this->adapter->updateStream($path, $resource, $config);
    }

    /**
     * Read a file.
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

        if (! ($object = $this->adapter->read($path))) {
            return false;
        }

        return $object['contents'];
    }

    /**
     * Retrieves a read-stream for a path.
     *
     * @param string $path
     *
     * @return resource|false path resource or false when on failure
     */
    public function readStream($path)
    {
        $path = Util::normalizePath($path);
        $this->assertPresent($path);

        if (! $object = $this->adapter->readStream($path)) {
            return false;
        }

        return $object['stream'];
    }

    /**
     * Rename a file.
     *
     * @param string $path    path to file
     * @param string $newpath new path
     *
     * @throws FileExistsException
     * @throws FileNotFoundException
     *
     * @return bool success boolean
     */
    public function rename($path, $newpath)
    {
        $path = Util::normalizePath($path);
        $newpath = Util::normalizePath($newpath);
        $this->assertPresent($path);
        $this->assertAbsent($newpath);

        return (bool) $this->adapter->rename($path, $newpath);
    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath)
    {
        $path = Util::normalizePath($path);
        $newpath = Util::normalizePath($newpath);
        $this->assertPresent($path);
        $this->assertAbsent($newpath);

        return $this->adapter->copy($path, $newpath);
    }

    /**
     * Delete a file.
     *
     * @param string $path path to file
     *
     * @throws FileNotFoundException
     *
     * @return bool success boolean
     */
    public function delete($path)
    {
        $path = Util::normalizePath($path);
        $this->assertPresent($path);

        return $this->adapter->delete($path);
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname path to directory
     *
     * @return bool success boolean
     */
    public function deleteDir($dirname)
    {
        $dirname = Util::normalizePath($dirname);

        if ($dirname === '') {
            throw new RootViolationException('Root directories can not be deleted.');
        }

        return (bool) $this->adapter->deleteDir($dirname);
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, array $config = [])
    {
        $dirname = Util::normalizePath($dirname);
        $config = $this->prepareConfig($config);

        return (bool) $this->adapter->createDir($dirname, $config);
    }

    /**
     * List the filesystem contents.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array contents
     */
    public function listContents($directory = '', $recursive = false)
    {
        $directory = Util::normalizePath($directory);
        $contents = $this->adapter->listContents($directory, $recursive);
        $mapper = function ($entry) use ($directory, $recursive) {
            $entry = $entry + Util::pathinfo($entry['path']);

            if (! empty($directory) && strpos($entry['path'], $directory) === false) {
                return false;
            }

            if ($recursive === false && Util::dirname($entry['path']) !== $directory) {
                return false;
            }

            return $entry;
        };

        return array_values(array_filter(array_map($mapper, $contents)));
    }

    /**
     * Get a file's mime-type.
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

        if (! $object = $this->adapter->getMimetype($path)) {
            return false;
        }

        return $object['mimetype'];
    }

    /**
     * Get a file's timestamp.
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

        if (! $object = $this->adapter->getTimestamp($path)) {
            return false;
        }

        return $object['timestamp'];
    }

    /**
     * Get a file's visibility.
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

        if (($object = $this->adapter->getVisibility($path)) === false) {
            return false;
        }

        return $object['visibility'];
    }

    /**
     * Get a file's size.
     *
     * @param string $path path to file
     *
     * @return int|false file size or FALSE when fails
     *                   to check size of existing file
     */
    public function getSize($path)
    {
        $path = Util::normalizePath($path);

        if (($object = $this->adapter->getSize($path)) === false || !isset($object['size'])) {
            return false;
        }

        return (int) $object['size'];
    }

    /**
     * Get a file's size.
     *
     * @param string $path       path to file
     * @param string $visibility visibility
     *
     * @return bool success boolean
     */
    public function setVisibility($path, $visibility)
    {
        $path = Util::normalizePath($path);

        return (bool) $this->adapter->setVisibility($path, $visibility);
    }

    /**
     * Get a file's metadata.
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

        return $this->adapter->getMetadata($path);
    }

    /**
     * Get a file/directory handler.
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
     * Convert a config array to a Config object with the correct fallback.
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
     * Assert a file is present.
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
     * Assert a file is absent.
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
     * Plugins pass-through.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @throws BadMethodCallException
     *
     * @return mixed
     */
    public function __call($method, array $arguments)
    {
        try {
            return $this->invokePlugin($method, $arguments, $this);
        } catch (PluginNotFoundException $e) {
            throw new BadMethodCallException(
                'Call to undefined method '
                .__CLASS__
                .'::'.$method
            );
        }
    }
}
