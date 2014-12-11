<?php

namespace League\Flysystem;

interface ReadInterface
{
    /**
     * @const MODE_RECURSIVE enable recursive mode
     */
    const MODE_RECURSIVE = 1;

    /**
     * @const MODE_GLOB_ENABLE allows glob pattern matching
     */
    const MODE_GLOB_ENABLED = 2;

    /**
     * Check whether a file exists
     *
     * @param   string  $path
     * @return  array|boolean|null
     */
    public function has($path);

    /**
     * Read a file
     *
     * @param   string  $path
     * @return  array|false
     */
    public function read($path);

    /**
     * Read a file as a stream
     *
     * @param   string  $path
     * @return  array|false
     */
    public function readStream($path);

    /**
     * List contents of a directory
     *
     * @param   string $directory
     * @param int $mode
     * @return  array
     */
    public function listContents($directory = '', $mode = 0);

    /**
     * Get all the meta data of a file or directory
     *
     * @param   string  $path
     * @return  array|false
     */
    public function getMetadata($path);

    /**
     * Get all the meta data of a file or directory
     *
     * @param   string  $path
     * @return  array|false
     */
    public function getSize($path);

    /**
     * Get the mimetype of a file
     *
     * @param   string  $path
     * @return  array|false
     */
    public function getMimetype($path);

    /**
     * Get the timestamp of a file
     *
     * @param   string  $path
     * @return  array|false
     */
    public function getTimestamp($path);

    /**
     * Get the visibility of a file
     *
     * @param   string  $path
     * @return  array|false
     */
    public function getVisibility($path);
}
