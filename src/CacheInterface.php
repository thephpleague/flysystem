<?php

namespace League\Flysystem;

interface CacheInterface extends ReadInterface
{
    /**
     * Check whether the directory listing of a given directory is complete.
     *
     * @param string $dirname
     * @param bool   $recursive
     *
     * @return bool
     */
    public function isComplete($dirname, $recursive);

    /**
     * Set a directory to completely listed.
     *
     * @param string $dirname
     * @param bool   $recursive
     *
     * @return $this
     */
    public function setComplete($dirname, $recursive);

    /**
     * Store the contents of a directory.
     *
     * @param string $directory
     * @param array  $contents
     * @param bool   $recursive
     *
     * @return array contents
     */
    public function storeContents($directory, array $contents, $recursive);

    /**
     * Flush the cache.
     */
    public function flush();

    /**
     * Autosave trigger.
     */
    public function autosave();

    /**
     * Store the cache.
     */
    public function save();

    /**
     * Load the cache.
     */
    public function load();

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
     * Delete an object from cache.
     *
     * @param string $path object path
     *
     * @return $this
     */
    public function delete($path);

    /**
     * Delete all objects from from a directory.
     *
     * @param string $dirname directory path
     *
     * @return $this
     */
    public function deleteDir($dirname);

    /**
     * Update the metadata for an object.
     *
     * @param string $path     object path
     * @param array  $object   object metadata
     * @param bool   $autosave whether to trigger the autosave routine
     */
    public function updateObject($path, array $object, $autosave = false);

    /**
     * Store object hit miss.
     *
     * @param string $path
     *
     * @return $this
     */
    public function storeMiss($path);
}
