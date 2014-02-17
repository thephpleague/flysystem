<?php

namespace League\Flysystem;

interface CacheInterface extends ReadInterface
{
    /**
     * Check whether the directory listing of a given directory is complete
     *
     * @param   path  $dirname
     * @param   bool  $recursive
     * @return  bool
     */
    public function isComplete($dirname, $recursive);

    /**
     * Set a directory to completely listed
     *
     * @param  path  $dirname
     * @param  bool  $recursive
     */
    public function setComplete($dirname, $recursive);

    /**
     * Store the contents of a directory
     *
     * @param   path   $directory
     * @param   array  $contents
     * @param   bool   $recursive
     * @return  array  contents
     */
    public function storeContents($directory, array $contents, $recursive);

    /**
     * Flush the cache
     *
     * @return  void
     */
    public function flush();

    /**
     * Autosave trigger
     *
     * @return  void
     */
    public function autosave();

    /**
     * Store the cache
     *
     * @return  void
     */
    public function save();

    /**
     * Load the cache
     *
     * @return  void
     */
    public function load();
}
