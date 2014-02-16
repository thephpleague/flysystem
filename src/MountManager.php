<?php

namespace League\Flysystem;

use LogicException;
use InvalidArgumentException;

class MountManager
{
    /**
     * @var  array  $filesystems
     */
    protected $filesystems = array();

    /**
     * Constructor
     *
     * @param   array  $filesystems
     */
    public function __construct(array $filesystems = array())
    {
        $this->mountFilesystems($filesystems);
    }

    /**
     * Mount filesystems
     *
     * @param   array  $filesystems  [:prefix => Filesystem,]
     * @return  $this
     */
    public function mountFilesystems(array $filesystems)
    {
        foreach ($filesystems as $prefix => $filesystem) {
            $this->mountFilesystem($prefix, $filesystem);
        }

        return $this;
    }

    /**
     * Mount filesystems
     *
     * @param   string               $prefix
     * @param   FilesystemInterface  $filesystem
     * @return  $this
     */
    public function mountFilesystem($prefix, FilesystemInterface $filesystem)
    {
        if ( ! is_string($prefix)) {
            throw new InvalidArgumentException(__METHOD__.' expects argument #1 to be a string.');
        }

        $this->filesystems[$prefix] = $filesystem;

        return $this;
    }

    /**
     * Get the filesystem with the corresponding prefix
     *
     * @param    string               $prefix
     * @return   FilesystemInterface
     * @throws   LogicException
     */
    public function getFilesystem($prefix)
    {
        if ( ! isset($this->filesystems[$prefix])) {
            throw new LogicException('No filesystem mounted with prefix ' . $prefix);
        }

        return $this->filesystems[$prefix];
    }

    /**
     * Retrieve the prefix form an arguments array
     *
     * @param   array  $arguments
     * @return  array  [:prefix, :arguments]
     */
    public function filterPrefix(array $arguments)
    {
        if (empty($arguments)) {
            throw new LogicException('At least one argument needed');
        }

        $path = array_shift($arguments);

        if ( ! is_string($path)) {
            throw new InvalidArgumentException('First argument should be a string');
        }

        if ( ! preg_match('#^[a-zA-Z0-9]+\:\/\/.*#', $path)) {
            throw new InvalidArgumentException('No prefix detected in for path: ' . $path);
        }

        list ($prefix, $path) = explode('://', $path, 2);
        array_unshift($arguments, $path);

        return array($prefix, $arguments);
    }

    /**
     * Call forwarder
     *
     * @param   string  $method
     * @param   array   $arguments
     * @return  mixed
     */
    public function __call($method, $arguments)
    {
        list($prefix, $arguments) = $this->filterPrefix($arguments);

        $filesystem = $this->getFilesystem($prefix);
        $callback = array($filesystem, $method);

        return call_user_func_array($callback, $arguments);
    }
}
