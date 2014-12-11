<?php

namespace League\Flysystem;

interface CacheInterface extends ReadInterface
{
    /**
     * Check whether the directory listing of a given directory is complete
     *
     * @param string $dirname
     * @param bool   $recursive
     *
     * @return bool
     */
    public function isComplete($dirname, $recursive);

    /**
     * Set a directory to completely listed
     *
     * @param string $dirname
     * @param bool   $recursive
     *
     * @return $this
     */
    public function setComplete($dirname, $recursive);

    /**
     * Store the contents of a directory
     *
     * @param string $directory
     * @param array  $contents
     * @param bool   $recursive
     *
     * @return array contents
     */
    public function storeContents($directory, array $contents, $recursive);

    /**
     * Flush the cache
     *
     * @return void
     */
    public function flush();

    /**
     * Autosave trigger
     *
     * @return void
     */
    public function autosave();

    /**
     * Store the cache
     *
     * @return void
     */
    public function save();

    /**
     * Load the cache
     *
     * @return void
     */
    public function load();

    /**
     * Delete an object from cache
     *
     * @param string $path object path
     *
     * @return $this
     */
    public function delete($path);

    /**
     * Update the metadata for an object
     *
     * @param string  $path     object path
     * @param array   $object   object metadata
     * @param boolean $autosave whether to trigger the autosave routine
     */
    public function updateObject($path, array $object, $autosave = false);

    /**
     * Store object hit miss
     *
     * @param string $path
     *
     * @return $this
     */
    public function storeMiss($path);
}
