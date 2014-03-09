<?php

namespace League\Flysystem\Adapter;

use Finfo;
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
     * Constructor
     *
     * @param  string  $root
     */
    public function __construct($root)
    {
        $root = $this->ensureDirectory($root);
        $this->root = Util::normalizePrefix($root, DIRECTORY_SEPARATOR);
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
     * Prefix a path with the root
     *
     * @param   string  $path
     * @return  string  prefixed path
     */
    protected function prefix($path)
    {
        if (empty($path)) {
            return $this->root;
        }

        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        return $this->root.Util::normalizePath($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Check whether a file is present
     *
     * @param   string   $path
     * @return  boolean
     */
    public function has($path)
    {
        return file_exists($this->prefix($path));
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
        $location = $this->prefix($path);
        $config = Util::ensureConfig($config);
        $this->ensureDirectory(dirname($location));

        if (($size = file_put_contents($location, $contents, LOCK_EX)) === false) {
            return false;
        }

        $type = 'file';
        $result = compact('contents', 'type', 'size',  'path');

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
        rewind($resource);
        $config = Util::ensureConfig($config);
        $location = $this->prefix($path);
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
        $stream = fopen($this->prefix($path), 'r');

        return compact('stream', 'path');
    }

    /**
     * Update a file using a stream
     *
     * @param $path
     * @param $resource
     * @return array|bool
     */
    public function updateStream($path, $resource)
    {
        return $this->writeStream($path, $resource);
    }

    /**
     * Update a file
     *
     * @param $path
     * @param $contents
     * @return array|bool
     */
    public function update($path, $contents)
    {
        $location = $this->prefix($path);
        $mimetype = Util::contentMimetype($contents);

        if (($size = file_put_contents($location, $contents, LOCK_EX)) === false) {
            return false;
        }

        return compact('path', 'size', 'contents', 'mimetype');
    }

    /**
     * Read a file
     *
     * @param $path
     * @return array|bool
     */
    public function read($path)
    {
        if (($contents = file_get_contents($this->prefix($path))) === false) {
            return false;
        }

        return array(
            'contents' => $contents,
            'path' => $path
        );
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
        $location = $this->prefix($path);
        $destination = $this->prefix($newpath);

        return rename($location, $destination);
    }


    /**
     * Delete a file
     *
     * @param $path
     * @return bool
     */
    public function delete($path)
    {
        return unlink($this->prefix($path));
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
        return $this->directoryContents($directory, $recursive);
    }

    /**
     * Get the metadata of a file
     *
     * @param $path
     * @return array
     */
    public function getMetadata($path)
    {
        $location = $this->prefix($path);
        $info = new SplFileInfo($location);

        return $this->normalizeFileInfo($path, $info);
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
        $location = $this->prefix($path);
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
        $location = $this->prefix($path);
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
        $location = $this->prefix($path);
        chmod($location, static::$permissions[$visibility]);

        return compact('visibility');
    }

    /**
     * Create a directory
     *
     * @param $dirname
     * @return array
     */
    public function createDir($dirname)
    {
        $location = $this->prefix($dirname);

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
        $location = $this->prefix($dirname);

        if ( ! is_dir($location)) {
            return false;
        }

        $contents = $this->directoryContents($dirname, true);
        $contents = array_reverse($contents);

        foreach ($contents as $file) {
            if ($file['type'] === 'file') {
                unlink($this->prefix($file['path']));
            } else {
                rmdir($this->prefix($file['path']));
            }
        }

        return rmdir($location);
    }

    /**
     * Get contents of a directory
     *
     * @param string $path
     * @param bool $recursive
     * @return array
     */
    protected function directoryContents($path = '', $recursive = false)
    {
        $result = array();
        $location = $this->prefix($path).DIRECTORY_SEPARATOR;

        if ( ! is_dir($location)) {
            return array();
        }

        $length = strlen($this->root);
        $iterator = $recursive ? $this->getRecursiveDirectoryIterator($location) : $this->getDirectoryIterator($location);

        foreach ($iterator as $file) {
            $path = substr($file->getPathname(), $length);
            $path = trim($path, '\\/');
            if (preg_match('#(^|/)\.{1,2}$#', $path)) continue;
            $result[] = $this->normalizeFileInfo($path, $file);
        }

        return $result;
    }

    /**
     * Normalize the file info
     *
     * @param $path
     * @param SplFileInfo $file
     * @return array
     */
    protected function normalizeFileInfo($path, SplFileInfo $file)
    {
        $normalized = array('type' => $file->getType(), 'path' => $path);

        if ($normalized['type'] === 'file') {
            $normalized['timestamp'] = $file->getMTime();
            $normalized['size'] = $file->getSize();
        }

        return $normalized;
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
