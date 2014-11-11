<?php

namespace League\Flysystem\Adapter\Polyfill;

use League\Flysystem\Config;

trait StreamedWritingTrait
{
    /**
     * Stream fallback delegator
     *
     * @param   string    $path
     * @param   resource  $resource
     * @param   Config    $config
     * @param   string    $fallback
     * @return  mixed     fallback result
     */
    protected function stream($path, $resource, Config $config, $fallback)
    {
        $contents = stream_get_contents($resource);

        return $this->{$fallback}($path, $contents, $config);
    }

    /**
     * Write using a stream
     *
     * @param   string  $path
     * @param   resource  $resource
     * @param   Config     $config
     * @return  mixed     false or file metadata
     */
    public function writeStream($path, $resource, Config $config)
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
    public function updateStream($path, $resource, Config $config)
    {
        return $this->stream($path, $resource, $config, 'update');
    }
}
