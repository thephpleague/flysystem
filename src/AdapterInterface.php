<?php

namespace League\Flysystem;

interface AdapterInterface extends ReadInterface
{
    /**
     * @const  VISIBILITY_PUBLIC  public visibility
     */
    const VISIBILITY_PUBLIC = 'public';

    /**
     * @const  VISIBILITY_PRIVATE  private visibility
     */
    const VISIBILITY_PRIVATE = 'private';

    /**
     * Write a new file
     *
     * @param   string       $path
     * @param   string       $contents
     * @param   mixed        $config   Config object or visibility setting
     * @return  false|array  false on failure file meta data on success
     */
    public function write($path, $contents, $config = null);

    /**
     * Update a file
     *
     * @param   string       $path
     * @param   string       $contents
     * @param   mixed        $config   Config object or visibility setting
     * @return  false|array  false on failure file meta data on success
     */
    public function update($path, $contents, $config = null);

    /**
     * Write a new file using a stream
     *
     * @param   string       $path
     * @param   resource     $resource
     * @param   mixed        $config   Config object or visibility setting
     * @return  false|array  false on failure file meta data on success
     */
    public function writeStream($path, $resource, $config = null);

    /**
     * Update a file using a stream
     *
     * @param   string       $path
     * @param   resource     $resource
     * @param   mixed        $config   Config object or visibility setting
     * @return  false|array  false on failure file meta data on success
     */
    public function updateStream($path, $resource, $config = null);

    /**
     * Rename a file
     *
     * @param   string  $path
     * @param   string  $newpath
     * @return  boolean
     */
    public function rename($path, $newpath);

    /**
     * Copy a file
     *
     * @param   string  $path
     * @param   string  $newpath
     * @return  boolean
     */
    public function copy($path, $newpath);

    /**
     * Delete a file
     *
     * @param   string  $path
     * @return  boolean
     */
    public function delete($path);

    /**
     * Delete a directory
     *
     * @param   string  $dirname
     * @return  boolean
     */
    public function deleteDir($dirname);

    /**
     * Create a directory
     *
     * @param   string       $dirname directory name
     * @param   array|Config $options
     *
     * @return  bool
     */
    public function createDir($dirname, $options = null);

    /**
     * Set the visibility for a file
     *
     * @param   string  $path
     * @param   string  $visibility
     * @return  array   file meta data
     */
    public function setVisibility($path, $visibility);
}
