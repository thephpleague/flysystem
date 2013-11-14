<?php

namespace Flysystem\Adapter;

use Finfo;
use SplFileInfo;
use FilesystemIterator;
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
        $root = realpath($root);

        $this->root = Util::normalizePrefix($root, DIRECTORY_SEPARATOR);
    }

    /**
     * Prefix a path with the root
     *
     * @param   string  $path
     * @return  string  prefixed path
     */
    protected function prefix($path)
    {
        return $this->root.Util::normalizePath($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Check wether a file is present
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

        $this->setVisibility($path, $visibility);

        return array(
            'contents' => $contents,
            'type' => 'file',
            'size' => $size,
            'visibility' => $visibility,
            'mimetype' => Util::contentMimetype($contents),
        );
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

    public function listContents()
    {
        return $this->directoryContents('', true);
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
                unlink($location.DIRECTORY_SEPARATOR.$file['path']);
            } else {
                rmdir($location.DIRECTORY_SEPARATOR.$file['path']);
            }
        }

        return rmdir($location);
    }

    protected function directoryContents($path = '', $info = true)
    {
        $result = array();
        $path = $this->prefix($path).DIRECTORY_SEPARATOR;
        $length = strlen($path);
        $iterator = $this->getDirectoryIterator($path);

        foreach ($iterator as $file) {
            $path = substr($file->getPathname(), $length);
            $result[] = $info ? $this->normalizeFileInfo($path, $file) : $path;
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

    protected function getDirectoryIterator($path)
    {
        $directory = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);

        return $iterator;
    }
}
