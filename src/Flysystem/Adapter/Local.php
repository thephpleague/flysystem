<?php

namespace Flysystem\Adapter;

use Finfo;
use SplFileInfo;
use FilesystemIterator;
use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Flysystem\Util;
use Flysystem\AdapterInterface;

class Local extends AbstractAdapter
{
    protected static $permissions = array(
        'public' => 0644,
        'private' => 0000,
    );

    /**
     * Constructor
     *
     * @param  string  $root
     */
    public function __construct($root)
    {
        $root = $this->ensureRootDirectory($root);

        $this->root = Util::normalizePrefix($root, DIRECTORY_SEPARATOR);
    }

    /**
     * Ensure the root directory exists.
     *
     * @param   string  $root  root directory path
     * @return  string  real path to root
     */
    protected function ensureRootDirectory($root)
    {
        if ( ! is_dir($root)) {
            mkdir($root, 0777, true);
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

    public function write($path, $contents, $visibility = null)
    {
        $location = $this->prefix($path);

        if ( ! is_dir($dirname = dirname($location))) {
            mkdir($dirname, 0777, true);
        }

        if (($size = file_put_contents($location, $contents, LOCK_EX)) === false) {
            return false;
        }

        if ($visibility)
            $this->setVisibility($path, $visibility);

        return array(
            'contents' => $contents,
            'type' => 'file',
            'size' => $size,
            'visibility' => $visibility,
            'mimetype' => Util::contentMimetype($contents),
        );
    }

    public function writeStream($path, $resource, $visibility = null)
    {
        rewind($resource);

        if ( ! $stream = fopen($this->prefix($path), 'w+')) {
            return false;
        }

        while ( ! feof($resource)) {
            fwrite($stream, fread($resource, 1024), 1024);
        }

        if ( ! fclose($stream)) {
            return false;
        }

        $visibility and $this->setVisibility($path, $visibility);

        return compact('path', 'visibility');
    }

    public function readStream($path)
    {
        $stream = fopen($this->prefix($path), 'r+');

        return compact('stream', 'path');
    }

    public function updateStream($path, $resource)
    {
        return $this->writeStream($path, $resource);
    }

    public function update($path, $contents)
    {
        $location = $this->prefix($path);

        if (($size = file_put_contents($location, $contents, LOCK_EX)) === false) {
            return false;
        }

        return array(
            'size' => $size,
            'contents' => $contents,
            'mimetype' => Util::contentMimetype($contents),
        );
    }

    public function read($path)
    {
        if (($contents = file_get_contents($this->prefix($path))) === false) {
            return false;
        }

        return array('contents' => $contents);
    }

    public function rename($path, $newpath)
    {
        $location = $this->prefix($path);
        $destination = $this->prefix($newpath);

        return rename($location, $destination);
    }

    public function delete($path)
    {
        return unlink($this->prefix($path));
    }

    public function listContents($directory = '', $recursive = false)
    {
        return $this->directoryContents($directory, $recursive);
    }

    public function getMetadata($path)
    {
        $location = $this->prefix($path);
        $info = new SplFileInfo($location);

        return $this->normalizeFileInfo($path, $info);
    }

    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    public function getMimetype($path)
    {
        $location = $this->prefix($path);
        $finfo = new Finfo(FILEINFO_MIME_TYPE);

        return array('mimetype' => $finfo->file($location));
    }

    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    public function getVisibility($path)
    {
        $location = $this->prefix($path);
        clearstatcache(false, $location);
        $permissions = octdec(substr(sprintf('%o', fileperms($location)), -4));
        $visibility = $permissions & 0044 ? AdapterInterface::VISIBILITY_PUBLIC : AdapterInterface::VISIBILITY_PRIVATE;

        return compact('visibility');
    }

    public function setVisibility($path, $visibility)
    {
        $location = $this->prefix($path);
        chmod($location, static::$permissions[$visibility]);

        return compact('visibility');
    }

    public function createDir($dirname)
    {
        $location = $this->prefix($dirname);

        if ( ! is_dir($location)) {
            mkdir($location, 0777, true);
        }

        return array('path' => $dirname, 'type' => 'dir');
    }

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

    protected function directoryContents($path = '', $recursive = false)
    {
        $result = array();
        $location = $this->prefix($path).DIRECTORY_SEPARATOR;
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

    protected function normalizeFileInfo($path, $file)
    {
        $normalized = array('type' => $file->getType(), 'path' => $path);

        if ($normalized['type'] === 'file') {
            $normalized['timestamp'] = $file->getMTime();
            $normalized['size'] = $file->getSize();
        }

        return $normalized;
    }

    protected function getRecursiveDirectoryIterator($path)
    {
        $directory = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);

        return $iterator;
    }

    protected function getDirectoryIterator($path)
    {
        $iterator = new DirectoryIterator($path);

        return $iterator;
    }
}
