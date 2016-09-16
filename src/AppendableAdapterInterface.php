<?php
namespace League\Flysystem;

interface AppendableAdapterInterface
{
    /**
     * Append file stream
     *
     * @param string $path
     * @param resource $resource
     * @param \League\Flysystem\Config $config
     * @return array|false
     */
    public function appendStream($path, $resource, Config $config);
}
