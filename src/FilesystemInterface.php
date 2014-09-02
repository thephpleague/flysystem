<?php

namespace League\Flysystem;

interface FilesystemInterface extends AdapterInterface
{
    /**
     * Create a file or update if exists
     *
     * @param  string              $path     path to file
     * @param  string              $contents file contents
     * @param  mixed               $config
     * @throws FileExistsException
     * @return boolean             success boolean
     */
    public function put($path, $contents, $config = null);

    /**
     * Create a file or update if exists using a stream
     *
     * @param   string    $path
     * @param   resource  $resource
     * @param   mixed     $config
     * @return  boolean   success boolean
     */
    public function putStream($path, $resource, $config = null);

    /**
     * Read and delete a file.
     *
     * @param   string  $path
     * @return  string  file contents
     * @throws  FileNotFoundException
     */
    public function readAndDelete($path);

    /**
     * List all files in the directory
     *
     * @param string      $directory
     * @param bool        $recursive
     *
     * @return array
     */
    public function listFiles($directory = '', $recursive = false);

    /**
     * List all paths
     *
     * @param   string  $directory
     * @param   bool    $recursive
     * @return  array   paths
     */
    public function listPaths($directory = '', $recursive = false);

    /**
     * List contents with metadata
     *
     * @param   array   $keys metadata key
     * @param   string  $directory
     * @param   bool    $recursive
     * @return  array            listing with metadata
     */
    public function listWith(array $keys = array(), $directory = '', $recursive = false);

    /**
     * Get metadata for an object with required metadata
     *
     * @param   string  $path      path to file
     * @param   array   $metadata  metadata keys
     * @throws  InvalidArgumentException
     * @return  array   metadata
     */
    public function getWithMetadata($path, array $metadata);

    /**
     * Get a file/directory handler
     *
     * @param   string   $path
     * @param   Handler  $handler
     * @return  Handler  file or directory handler
     */
    public function get($path, Handler $handler = null);

    /**
     * Flush the cache
     *
     * @return  $this
     */
    public function flushCache();

    /**
     * Register a plugin
     *
     * @param   PluginInterface  $plugin
     * @return  $this
     */
    public function addPlugin(PluginInterface $plugin);
}
