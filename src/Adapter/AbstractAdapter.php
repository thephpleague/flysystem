<?php

namespace League\Flysystem\Adapter;

use LogicException;
use League\Flysystem\AdapterInterface;

abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * @var  string  $prefixPrefix  path prefix
     */
    protected $pathPrefix;

    /**
     * @var  string  $pathSeparator
     */
    protected $pathSeparator = '/';

    /**
     * Set the path prefix
     *
     * @param   string  $prefix
     * @return  self
     */
    public function setPathPrefix($prefix)
    {
        $is_empty = empty($prefix);

        if ( ! $is_empty) {
            $prefix = rtrim($prefix, $this->pathSeparator) . $this->pathSeparator;
        }

        $this->pathPrefix = $is_empty ? null : $prefix;
    }

    /**
     * Get the path prefix
     *
     * @return  string  path prefix
     */
    public function getPathPrefix()
    {
        return $this->pathPrefix;
    }

    /**
     * Prefix a path
     *
     * @param   string  $path
     * @return  string  prefixed path
     */
    public function applyPathPrefix($path)
    {
        $path = ltrim($path, '\\/');

        if (strlen($path) === 0) {
            return $this->getPathPrefix() ?: '';
        }

        if ($prefix = $this->getPathPrefix()) {
            $path = $prefix . $path;
        }

        return $path;
    }

    /**
     * Remove a path prefix
     *
     * @param   string  $path
     * @return  string  path without the prefix
     */
    public function removePathPrefix($path)
    {
        if ($this->pathPrefix === null) {
            return $path;
        }

        $length = strlen($this->pathPrefix);

        return substr($path, $length);
    }

    /**
     * Write using a stream
     *
     * @param   string  $path
     * @param   resource  $resource
     * @param   mixed     $config
     * @return  mixed     false or file metadata
     */
    public function writeStream($path, $resource, $config = null)
    {
        return $this->stream($path, $resource, $config, 'write');
    }

    /**
     * Update a file using a stream
     *
     * @param   string    $path
     * @param   resource  $resource
     * @param   mixed     $config   Config object or visibility setting
     * @return  mixed     false of file metadata
     */
    public function updateStream($path, $resource, $config = null)
    {
        return $this->stream($path, $resource, $config, 'update');
    }

    /**
     * Get the contents of a file in a stream
     *
     * @param   string          $path
     * @return  resource|false  false when not found, or a resource
     */
    public function readStream($path)
    {
        if ( ! $data = $this->read($path)) {
            return false;
        }

        $stream = tmpfile();
        fwrite($stream, $data['contents']);
        rewind($stream);

        $data['stream'] = $stream;

        return $data;
    }

    /**
     * Stream fallback
     *
     * @param   string    $path
     * @param   resource  $resource
     * @param   mixed     $config
     * @param   string    $fallback
     * @return  mixed     fallback result
     */
    protected function stream($path, $resource, $config, $fallback)
    {
        $contents = stream_get_contents($resource);

        return $this->{$fallback}($path, $contents, $config);
    }

    /**
     * Get the file visibility
     *
     * @param   string  $path
     * @throws  LogicException
     */
    public function getVisibility($path)
    {
        throw new LogicException(get_class($this).' does not support visibility settings.');
    }

    /**
     * Set the file visibility
     *
     * @param   string  $path
     * @param   string  $visibility
     * @throws  LogicException
     */
    public function setVisibility($path, $visibility)
    {
        throw new LogicException(get_class($this).' does not support visibility settings.');
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
        $data = $this->readStream($path);

        if ( ! is_resource($data['stream'])) {
            return false;
        }

        $result = $this->writeStream($newpath, $data['stream']);

        if (is_resource($data['stream'])) {
            fclose($data['stream']);
        }

        if ( ! $result) {
            return false;
        }

        return true;
    }
}
