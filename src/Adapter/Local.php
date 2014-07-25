<?php

namespace League\Flysystem\Adapter;

use Finfo;
use League\Flysystem\Config;
use SplFileInfo;
use FilesystemIterator;
use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use League\Flysystem\Util;
use League\Flysystem\AdapterInterface;

class Local extends AbstractAdapter
{
    protected static $permissions = array(
        'public' => 0744,
        'private' => 0700,
    );

    /**
     * @var  string  $pathSeparator
     */
    protected $pathSeparator = DIRECTORY_SEPARATOR;

    /**
     * Constructor
     *
     * @param  string  $root
     */
    public function __construct($root)
    {
        $root = $this->ensureDirectory($root);
        $this->setPathPrefix($root);
    }

    /**
     * Ensure the root directory exists.
     *
     * @param   string  $root  root directory path
     * @return  string  real path to root
     */
    protected function ensureDirectory($root)
    {
        if ( ! is_dir($root)) {
            mkdir($root, 0755, true);
        }

        return realpath($root);
    }

    /**
     * Check whether a file is present
     *
     * @param   string   $path
     * @return  boolean
     */
    public function has($path)
    {
        $location = $this->applyPathPrefix($path);

        return is_file($location);
    }

    /**
     * Write a file
     *
     * @param $path
     * @param $contents
     * @param null $config
     * @return array|bool
     */
    public function write($path, $contents, $config = null)
    {
        $location = $this->applyPathPrefix($path);
        $config = Util::ensureConfig($config);
        $this->ensureDirectory(dirname($location));

        if (($size = file_put_contents($location, $contents, LOCK_EX)) === false) {
            return false;
        }

        $type = 'file';
        $result = compact('contents', 'type', 'size', 'path');

        if ($visibility = $config->get('visibility')) {
            $result['visibility'] = $visibility;
            $this->setVisibility($path, $visibility);
        }

        return $result;
    }

    /**
     * Write using a stream
     *
     * @param $path
     * @param $resource
     * @param null $config
     * @return array|bool
     */
    public function writeStream($path, $resource, $config = null)
    {
        $config = Util::ensureConfig($config);
        $location = $this->applyPathPrefix($path);
        $this->ensureDirectory(dirname($location));

        if ( ! $stream = fopen($location, 'w+')) {
            return false;
        }

        while ( ! feof($resource)) {
            fwrite($stream, fread($resource, 1024), 1024);
        }

        if ( ! fclose($stream)) {
            return false;
        }

        if ($visibility = $config->get('visibility')) {
            $this->setVisibility($path, $visibility);
        }

        return compact('path', 'visibility');
    }

    /**
     * Get a read-stream for a file
     *
     * @param $path
     * @return array|bool
     */
    public function readStream($path)
    {
        $location = $this->applyPathPrefix($path);
        $stream = fopen($location, 'r');

        return compact('stream', 'path');
    }

    /**
     * Update a file using a stream
     *
     * @param   string    $path
     * @param   resource  $resource
     * @param   mixed     $config   Config object or visibility setting
     * @return  array|bool
     */
    public function updateStream($path, $resource, $config = null)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * Update a file
     *
     * @param   string       $path
     * @param   string       $contents
     * @param   mixed        $config   Config object or visibility setting
     * @return  array|bool
     */
    public function update($path, $contents, $config = null)
    {
        $location = $this->applyPathPrefix($path);
        $mimetype = Util::guessMimeType($path, $contents);

        if (($size = file_put_contents($location, $contents, LOCK_EX)) === false) {
            return false;
        }

        return compact('path', 'size', 'contents', 'mimetype');
    }

    /**
     * Read a file
     *
     * @param   string  $path
     * @return  array|bool
     */
    public function read($path)
    {
        $location = $this->applyPathPrefix($path);
        $contents = file_get_contents($location);

        if ($contents === false) {
            return false;
        }

        return compact('contents', 'path');
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
        $location = $this->applyPathPrefix($path);
        $destination = $this->applyPathPrefix($newpath);

        return rename($location, $destination);
    }

    /**
     * Copy a file
     *
     * @param $path
     * @param $newpath
     * @return bool
     */
    public function copy($path, $newpath)
    {
        $location = $this->applyPathPrefix($path);
        $destination = $this->applyPathPrefix($newpath);
        $this->ensureDirectory(dirname($destination));

        return copy($location, $destination);
    }

    /**
     * Delete a file
     *
     * @param $path
     * @return bool
     */
    public function delete($path)
    {
        $location = $this->applyPathPrefix($path);

        return unlink($location);
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
        $result = array();
        $location = $this->applyPathPrefix($directory).$this->pathSeparator;

        if ( ! is_dir($location)) {
            return array();
        }

        $iterator = $recursive ? $this->getRecursiveDirectoryIterator($location) : $this->getDirectoryIterator($location);

        foreach ($iterator as $file) {
            $path = $this->getFilePath($file);
            if (preg_match('#(^|/)\.{1,2}$#', $path)) continue;
            $result[] = $this->normalizeFileInfo($file);
        }

        return $result;
    }

    /**
     * Get the metadata of a file
     *
     * @param $path
     * @return array
     */
    public function getMetadata($path)
    {
        $location = $this->applyPathPrefix($path);
        $info = new SplFileInfo($location);

        return $this->normalizeFileInfo($info);
    }

    /**
     * Get the size of a file
     *
     * @param $path
     * @return array
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the mimetype of a file
     *
     * @param $path
     * @return array
     */
    public function getMimetype($path)
    {
        $location = $this->applyPathPrefix($path);
        $finfo = new Finfo(FILEINFO_MIME_TYPE);

        return array('mimetype' => $finfo->file($location));
    }

    /**
     * Get the timestamp of a file
     *
     * @param $path
     * @return array
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the visibility of a file
     *
     * @param $path
     * @return array|void
     */
    public function getVisibility($path)
    {
        $location = $this->applyPathPrefix($path);
        clearstatcache(false, $location);
        $permissions = octdec(substr(sprintf('%o', fileperms($location)), -4));
        $visibility = $permissions & 0044 ? AdapterInterface::VISIBILITY_PUBLIC : AdapterInterface::VISIBILITY_PRIVATE;

        return compact('visibility');
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
        $location = $this->applyPathPrefix($path);
        chmod($location, static::$permissions[$visibility]);

        return compact('visibility');
    }

    /**
     * Create a directory
     *
     * @param   string       $dirname directory name
     * @param   array|Config $options
     *
     * @return  bool
     */
    public function createDir($dirname, $options = null)
    {
        $location = $this->applyPathPrefix($dirname);

        if ( ! is_dir($location)) {
            mkdir($location, 0777, true);
        }

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
        $location = $this->applyPathPrefix($dirname);

        if ( ! is_dir($location)) {
            return false;
        }

        $contents = $this->listContents($dirname, true);
        $contents = array_reverse($contents);

        foreach ($contents as $file) {
            if ($file['type'] === 'file') {
                unlink($this->applyPathPrefix($file['path']));
            } else {
                rmdir($this->applyPathPrefix($file['path']));
            }
        }

        return rmdir($location);
    }

    /**
     * Normalize the file info
     *
     * @param SplFileInfo $file
     * @return array
     */
    protected function normalizeFileInfo(SplFileInfo $file)
    {
        $normalized = array(
            'type' => $file->getType(),
            'path' => $this->getFilePath($file),
            'timestamp' => $file->getMTime()
        );

        if ($normalized['type'] === 'file') {
            $normalized['size'] = $file->getSize();
        }

        return $normalized;
    }

    /**
     * Get the normalized path from a SplFileInfo object
     *
     * @param   SplFileInfo  $file
     * @return  string
     */
    protected function getFilePath(SplFileInfo $file)
    {
        $path = $file->getPathname();
        $path = $this->removePathPrefix($path);

        return trim($path, '\\/');
    }

    /**
     * @param $path
     * @return RecursiveIteratorIterator
     */
    protected function getRecursiveDirectoryIterator($path)
    {
        $directory = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);

        return $iterator;
    }

    /**
     * @param $path
     * @return DirectoryIterator
     */
    protected function getDirectoryIterator($path)
    {
        $iterator = new DirectoryIterator($path);

        return $iterator;
    }
}
