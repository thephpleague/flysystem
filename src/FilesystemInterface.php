<?php

namespace League\Flysystem;

interface FilesystemInterface
{
    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function has($path);

    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return false|string
     */
    public function read($path);

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return false|resource
     */
    public function readStream($path);

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false);

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return false|array
     */
    public function getMetadata($path);

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return false|int
     */
    public function getSize($path);

    /**
     * Get the mime-type of a file.
     *
     * @param string $path
     *
     * @return false|string
     */
    public function getMimetype($path);

    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return false|int
     */
    public function getTimestamp($path);

    /**
     * Get the visibility of a file.
     *
     * @param string $path
     *
     * @return false|string
     */
    public function getVisibility($path);

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param array  $config   Config array
     *
     * @return bool success boolean
     */
    public function write($path, $contents, array $config = []);

    /**
     * Write a new file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param array    $config   config array
     *
     * @return bool success boolean
     */
    public function writeStream($path, $resource, array $config = []);

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param array  $config   config array
     *
     * @return bool success boolean
     */
    public function update($path, $contents, array $config = []);

    /**
     * Update a file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param array    $config   config array
     *
     * @return bool success boolean
     */
    public function updateStream($path, $resource, array $config = []);

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function rename($path, $newpath);

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath);

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path);

    /**
     * Delete a directory.
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function deleteDir($dirname);

    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param array  $config
     *
     * @return bool
     */
    public function createDir($dirname, array $config = []);

    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     *
     * @return bool success boolean
     */
    public function setVisibility($path, $visibility);

    /**
     * Create a file or update if exists.
     *
     * @param string $path     path to file
     * @param string $contents file contents
     * @param array  $config
     *
     * @throws FileExistsException
     *
     * @return bool success boolean
     */
    public function put($path, $contents, array $config = []);

    /**
     * Create a file or update if exists using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param array    $config
     *
     * @return bool success boolean
     */
    public function putStream($path, $resource, array $config = []);

    /**
     * Read and delete a file.
     *
     * @param string $path
     *
     * @throws FileNotFoundException
     *
     * @return string|false file contents
     */
    public function readAndDelete($path);

    /**
     * Get a file/directory handler.
     *
     * @param string  $path
     * @param Handler $handler
     *
     * @return Handler file or directory handler
     */
    public function get($path, Handler $handler = null);

    /**
     * Register a plugin.
     *
     * @param PluginInterface $plugin
     *
     * @return $this
     */
    public function addPlugin(PluginInterface $plugin);
}
