<?php

namespace League\Flysystem\Adapter;

use LogicException;
use League\Flysystem\AdapterInterface;

abstract class AbstractAdapter implements AdapterInterface
{
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
     * @return  mixed     false of file metadata
     */
    public function updateStream($path, $resource)
    {
        return $this->stream($path, $resource, null, 'update');
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
        rewind($resource);
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
}
