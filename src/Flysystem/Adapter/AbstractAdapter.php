<?php

namespace Flysystem\Adapter;

use LogicException;
use Flysystem\AdapterInterface;

abstract class AbstractAdapter implements AdapterInterface
{
    public function writeStream($path, $resource, $visibility = null)
    {
        return $this->stream($path, $resource, $visibility, 'write');
    }

    public function updateStream($path, $resource)
    {
        return $this->stream($path, $resource, null, 'update');
    }

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

    protected function stream($path, $resource, $visibility, $fallback)
    {
        rewind($resource);
        $contents = stream_get_contents($resource);

        return $this->{$fallback}($path, $contents, $visibility);
    }

    public function getVisibility($path)
    {
        throw new LogicException(get_class($this).' does not support visibility settings.');
    }

    public function setVisibility($path, $visibility)
    {
        throw new LogicException(get_class($this).' does not support visibility settings.');
    }
}
