<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Adapter\Polyfill\StreamedCopyTrait;
use League\Flysystem\Adapter\Polyfill\StreamedTrait;
use League\Flysystem\Config;
use League\Flysystem\Util;

class NullAdapter extends AbstractAdapter
{
    use StreamedTrait;
    use StreamedCopyTrait;

    /**
     * Check whether a file is present
     *
     * @param   string   $path
     * @return  boolean
     */
    public function has($path)
    {
        return false;
    }

    /**
     * Write a file
     *
     * @param $path
     * @param $contents
     * @param Config $config
     * @return array|bool
     */
    public function write($path, $contents, Config $config)
    {
        $type = 'file';
        $config = Util::ensureConfig($config);
        $result = compact('contents', 'type', 'size', 'path');

        if ($visibility = $config->get('visibility')) {
            $result['visibility'] = $visibility;
        }

        return $result;
    }

    /**
     * Update a file
     *
     * @param   string       $path
     * @param   string       $contents
     * @param   Config        $config   Config object or visibility setting
     * @return  boolean
     */
    public function update($path, $contents, Config $config)
    {
        return false;
    }

    /**
     * Read a file
     *
     * @param   string  $path
     * @return  boolean
     */
    public function read($path)
    {
        return false;
    }

    /**
     * Rename a file
     *
     * @param $path
     * @param $newpath
     * @return bool
     */
    public function rename($path, $newpath)
    {
        return false;
    }


    /**
     * Delete a file
     *
     * @param $path
     * @return bool
     */
    public function delete($path)
    {
        return false;
    }

    /**
     * List contents of a directory
     *
     * @param string $directory
     * @param bool $recursive
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {
        return array();
    }

    /**
     * Get the metadata of a file
     *
     * @param $path
     * @return boolean
     */
    public function getMetadata($path)
    {
        return false;
    }

    /**
     * Get the size of a file
     *
     * @param $path
     * @return boolean
     */
    public function getSize($path)
    {
        return false;
    }

    /**
     * Get the mimetype of a file
     *
     * @param $path
     * @return boolean
     */
    public function getMimetype($path)
    {
        return false;
    }

    /**
     * Get the timestamp of a file
     *
     * @param $path
     * @return boolean
     */
    public function getTimestamp($path)
    {
        return false;
    }

    /**
     * Get the visibility of a file
     *
     * @param $path
     * @return boolean
     */
    public function getVisibility($path)
    {
        return false;
    }

    /**
     * Set the visibility of a file
     *
     * @param $path
     * @param $visibility
     * @return array|void
     */
    public function setVisibility($path, $visibility)
    {
        return compact('visibility');
    }

    /**
     * Create a directory
     *
     * @param   string       $dirname directory name
     *
     * @return  bool
     */
    public function createDir($dirname, Config $config)
    {
        return array('path' => $dirname, 'type' => 'dir');
    }

    /**
     * Delete a directory
     *
     * @param $dirname
     * @return bool
     */
    public function deleteDir($dirname)
    {
        return false;
    }
}
