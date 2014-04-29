<?php

namespace League\Flysystem;

interface FilesystemInterface extends AdapterInterface
{
    public function put($path, $contents, $visibility = null);
    public function putStream($path, $resource, $visibility = null);
    public function readAndDelete($path);
    public function listPaths($directory = '', $recursive = false);
    public function listWith(array $keys = array(), $directory = '', $recursive = false);
    public function getWithMetadata($path, array $metadata);
    public function get($path, Handler $handler = null);
    public function flushCache();
    public function addPlugin(PluginInterface $plugin);
}
