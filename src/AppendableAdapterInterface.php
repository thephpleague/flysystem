<?php
namespace League\Flysystem;

interface AppendableAdapterInterface extends AdapterInterface
{
    /**
     * Append file
     *
     * @param string   $path
     * @param string   $contents
     * @param Config   $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function append($path, $contents, Config $config);

    /**
     * Append file stream
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function appendStream($path, $resource, Config $config);
}
