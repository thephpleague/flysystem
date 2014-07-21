<?php

namespace League\Flysystem;

use League\Event\EmitterTrait;
use League\Event\Emitter;
use League\Flysystem\Event\Before as BeforeEvent;
use League\Flysystem\Event\After as AfterEvent;

class EventableFilesystem implements FilesystemInterface
{
    use EmitterTrait;

    /**
     * @var  FilesystemInterface  $filesystem
     */
    protected $filesystem;

    /**
     * Constructor
     *
     * @param  AdapterInterface  $adapter
     * @param  CacheInterface    $cache
     * @param  null              $config
     * @param  Emitter           $emitter
     */
    public function __construct(AdapterInterface $adapter, CacheInterface $cache = null, $config = null, Emitter $emitter = null)
    {
        $this->filesystem = $this->prepareAdapter($adapter, $cache, $config);
        $this->setEmitter($emitter);
    }

    public function getFilesystem()
    {
        return $this->filesystem;
    }

    protected function prepareAdapter(AdapterInterface $adapter, CacheInterface $cache = null, $config = null)
    {
        if ($adapter instanceof FilesystemInterface) {
            return $adapter;
        }

        return new Filesystem($adapter, $cache, $config);
    }

    public function put($path, $contents, $config = null)
    {
        return $this->delegateMethodCall('put', compact('path', 'contents', 'config'));
    }

    public function putStream($path, $resource, $config = null)
    {
        return $this->delegateMethodCall('putStream', compact('path', 'resource', 'config'));
    }

    public function readAndDelete($path, $config = null)
    {
        return $this->delegateMethodCall('readAndDelete', compact('path', 'config'));
    }

    public function listPaths($directory = '', $recursive = false, $config = null)
    {
        return $this->delegateMethodCall('listPaths', compact('directory', 'recursive', 'config'));
    }

    public function listWith(array $keys = array(), $directory = '', $recursive = false, $config = null)
    {
        return $this->delegateMethodCall('listWith', compact('keys', 'directory', 'recursive', 'config'));
    }

    public function getWithMetadata($path, array $metadata, $config = null)
    {
        return $this->delegateMethodCall('getWithMetadata', compact('path', 'metadata', 'config'));
    }

    public function get($path, Handler $handler = null, $config = null)
    {
        return $this->delegateMethodCall('get', compact('path', 'handler', 'config'));
    }

    public function flushCache($config = null)
    {
        return $this->delegateMethodCall('flushCache', compact('config'));
    }

    public function addPlugin(PluginInterface $plugin, $config = null)
    {
        return $this->delegateMethodCall('addPlugin', compact('plugin', 'config'));
    }

    /**
     * Check whether a file exists
     *
     * @param   string  $path
     * @return  bool
     */
    public function has($path, $config = null)
    {
        return $this->delegateMethodCall('has', compact('path', 'config'));
    }

    /**
     * Read a file
     *
     * @param   string  $path
     * @return  false|array
     */
    public function read($path, $config = null)
    {
        return $this->delegateMethodCall('read', compact('path', 'config'));
    }

    /**
     * Read a file as a stream
     *
     * @param   string  $path
     * @return  false|array
     */
    public function readStream($path, $config = null)
    {
        return $this->delegateMethodCall('readStream', compact('path', 'config'));
    }

    /**
     * List contents of a directory
     *
     * @param   string  $directory
     * @param   bool    $recursive
     * @return  false|array
     */
    public function listContents($directory = '', $recursive = false, $config = null)
    {
        return $this->delegateMethodCall('listContents', compact('directory', 'recursive', 'config'));
    }

    /**
     * Get all the meta data of a file or directory
     *
     * @param   string  $path
     * @return  false|array
     */
    public function getMetadata($path, $config = null)
    {
        return $this->delegateMethodCall('getMetadata', compact('path', 'config'));
    }

    /**
     * Get all the meta data of a file or directory
     *
     * @param   string  $path
     * @return  false|array
     */
    public function getSize($path, $config = null)
    {
        return $this->delegateMethodCall('getSize', compact('path', 'config'));
    }

    /**
     * Get the mimetype of a file
     *
     * @param   string  $path
     * @return  false|array
     */
    public function getMimetype($path, $config = null)
    {
        return $this->delegateMethodCall('getMimetype', compact('path', 'config'));
    }

    /**
     * Get the timestamp of a file
     *
     * @param   string  $path
     * @return  false|array
     */
    public function getTimestamp($path, $config = null)
    {
        return $this->delegateMethodCall('getTimestamp', compact('path', 'config'));
    }

    /**
     * Get the visibility of a file
     *
     * @param   string  $path
     * @return  false|array
     */
    public function getVisibility($path, $config = null)
    {
        return $this->delegateMethodCall('getVisibility', compact('path', 'config'));
    }

    /**
     * Write a new file
     *
     * @param   string       $path
     * @param   string       $contents
     * @param   mixed        $config   Config object or visibility setting
     * @return  false|array  false on failure file meta data on success
     */
    public function write($path, $contents, $config = null)
    {
        return $this->delegateMethodCall('write', compact('path', 'contents', 'config'));
    }

    /**
     * Update a file
     *
     * @param   string       $path
     * @param   string       $contents
     * @param   mixed        $config   Config object or visibility setting
     * @return  false|array  false on failure file meta data on success
     */
    public function update($path, $contents, $config = null)
    {
        return $this->delegateMethodCall('update', compact('path', 'contents', 'config'));
    }

    /**
     * Write a new file using a stream
     *
     * @param   string       $path
     * @param   resource     $resource
     * @param   mixed        $config   Config object or visibility setting
     * @return  false|array  false on failure file meta data on success
     */
    public function writeStream($path, $resource, $config = null)
    {
        return $this->delegateMethodCall('writeStream', compact('path', 'resource', 'config'));
    }

    /**
     * Update a file using a stream
     *
     * @param   string       $path
     * @param   resource     $resource
     * @param   mixed        $config   Config object or visibility setting
     * @return  false|array  false on failure file meta data on success
     */
    public function updateStream($path, $resource, $config = null)
    {
        return $this->delegateMethodCall('updateStream', compact('path', 'resource', 'config'));
    }

    /**
     * Rename a file
     *
     * @param   string  $path
     * @param   string  $newpath
     * @return  boolean
     */
    public function rename($path, $newpath, $config = null)
    {
        return $this->delegateMethodCall('rename', compact('path', 'newpath', 'config'));
    }

    /**
     * Copy a file
     *
     * @param   string  $path
     * @param   string  $newpath
     * @return  boolean
     */
    public function copy($path, $newpath, $config = null)
    {
        return $this->delegateMethodCall('copy', compact('path', 'newpath', 'config'));
    }

    /**
     * Delete a file
     *
     * @param   string  $path
     * @return  boolean
     */
    public function delete($path, $config = null)
    {
        return $this->delegateMethodCall('delete', compact('path', 'config'));
    }

    /**
     * Delete a directory
     *
     * @param   string  $dirname
     * @return  boolean
     */
    public function deleteDir($dirname, $config = null)
    {
        return $this->delegateMethodCall('deleteDir', compact('dirname', 'config'));
    }

    /**
     * Create a directory
     *
     * @param   string       $dirname directory name
     * @param   array|Config $options
     *
     * @return  bool
     */
    public function createDir($dirname, $config = null)
    {
        return $this->delegateMethodCall('createDir', compact('dirname', 'config'));
    }

    /**
     * Set the visibility for a file
     *
     * @param   string  $path
     * @param   string  $visibility
     * @return  file meta data
     */
    public function setVisibility($path, $visibility, $config = null)
    {
        return $this->delegateMethodCall('setVisibility', compact('path', 'visibility', 'config'));
    }

    /**
     * @param       $method
     * @param array $arguments
     * @return mixed
     */
    public function delegateMethodCall($method, array $arguments = [])
    {
        $arguments = $this->prepareArguments($arguments);
        $config = $arguments['config'];

        if ($config->get('silent')) {
            return $this->callFilesystemMethod($method, $arguments);
        }

        list($continue, $result) = $this->emitBefore($method, $arguments);

        if ( ! $continue) {
            return $result;
        }

        $result = $this->callFilesystemMethod($method, $result);

        return $this->emitAfter($method, $result);
    }

    /**
     * Emit the before event
     *
     * @param   string  $method
     * @param   array   $arguments
     * @return  array   [continue, call result]
     */
    protected function emitBefore($method, $arguments)
    {
        $event = new BeforeEvent($this->filesystem, $method, $arguments);
        $this->emit($event, $method);

        if ($event->isPropagationStopped()) {
            return [false, $event->getResult()];
        }

        return [true, $event->getArguments()];
    }

    /**
     * @param $method
     * @param array $arguments
     * @return mixed
     */
    protected function callFilesystemMethod($method, array $arguments)
    {
        $callable = [$this->filesystem, $method];
        $result = call_user_func_array($callable, $arguments);

        return $result;
    }

    /**
     * @param $method
     * @param $result
     * @return mixed
     */
    protected function emitAfter($method, $result)
    {
        $event = new AfterEvent($this->filesystem, $method, $result);
        $this->emit($event);

        return $event->getResult();
    }

    /**
     * @param array $arguments
     * @return array
     */
    public function prepareArguments(array $arguments)
    {
        if ( ! isset($arguments['config'])) {
            $arguments['config'] = new Config;
        }

        if (is_array($arguments['config'])) {
            $arguments['config'] = new Config($arguments['config']);
        }

        return $arguments;
    }
}
