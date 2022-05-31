<?php

namespace League\Flysystem\Adapter;

use DirectoryIterator;
use FilesystemIterator;
use finfo as Finfo;
use League\Flysystem\Config;
use League\Flysystem\Exception;
use League\Flysystem\NotSupportedException;
use League\Flysystem\UnreadableFileException;
use League\Flysystem\Util;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class Local extends AbstractAdapter
{
    /**
     * @var int
     */
    const SKIP_LINKS = 0001;

    /**
     * @var int
     */
    const DISALLOW_LINKS = 0002;

    /**
     * @var array
     */
    protected static $permissions = [
        'file' => [
            'public' => 0644,
            'private' => 0600,
        ],
        'dir' => [
            'public' => 0755,
            'private' => 0700,
        ],
    ];

    /**
     * @var string
     */
    protected $pathSeparator = DIRECTORY_SEPARATOR;

    /**
     * @var array
     */
    protected $permissionMap;

    /**
     * @var int
     */
    protected $writeFlags;

    /**
     * @var int
     */
    private $linkHandling;

    /**
     * Constructor.
     *
     * @param string $root
     * @param int    $writeFlags
     * @param int    $linkHandling
     * @param array  $permissions
     *
     * @throws LogicException
     */
    public function __construct($root, $writeFlags = LOCK_EX, $linkHandling = self::DISALLOW_LINKS, array $permissions = [])
    {
        $root = is_link($root) ? realpath($root) : $root;
        $this->permissionMap = array_replace_recursive(static::$permissions, $permissions);
        $this->ensureDirectory($root);

        if ( ! is_dir($root) || ! is_readable($root)) {
            throw new LogicException('The root path ' . $root . ' is not readable.');
        }

        $this->setPathPrefix($root);
        $this->writeFlags = $writeFlags;
        $this->linkHandling = $linkHandling;
    }

    /**
     * Ensure the root directory exists.
     *
     * @param string $root root directory path
     *
     * @return void
     *
     * @throws Exception in case the root directory can not be created
     */
    protected function ensureDirectory($root)
    {
        if ( ! is_dir($root)) {
            $umask = umask(0);

            if ( ! @mkdir($root, $this->permissionMap['dir']['public'], true)) {
                $mkdirError = error_get_last();
            }

            umask($umask);
            clearstatcache(false, $root);

            if ( ! is_dir($root)) {
                $errorMessage = isset($mkdirError['message']) ? $mkdirError['message'] : '';
                throw new Exception(sprintf('Impossible to create the root directory "%s". %s', $root, $errorMessage));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function has($path)
    {
        $location = $this->applyPathPrefix($path);

        return file_exists($location);
    }

    /**
     * @inheritdoc
     */
    public function write($path, $contents, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        $this->ensureDirectory(dirname($location));

        if (($size = file_put_contents($location, $contents, $this->writeFlags)) === false) {
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
     * @inheritdoc
     */
    public function writeStream($path, $resource, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        $this->ensureDirectory(dirname($location));
        $stream = fopen($location, 'w+b');

        if ( ! $stream || stream_copy_to_stream($resource, $stream) === false || ! fclose($stream)) {
            return false;
        }

        $type = 'file';
        $result = compact('type', 'path');

        if ($visibility = $config->get('visibility')) {
            $this->setVisibility($path, $visibility);
            $result['visibility'] = $visibility;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function readStream($path)
    {
        $location = $this->applyPathPrefix($path);
        $stream = fopen($location, 'rb');

        if ($this->writeFlags & LOCK_EX && $stream !== false) {
            flock($stream, LOCK_SH);
        }

        return ['type' => 'file', 'path' => $path, 'stream' => $stream];
    }

    /**
     * @inheritdoc
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * @inheritdoc
     */
    public function update($path, $contents, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        $size = file_put_contents($location, $contents, $this->writeFlags);

        if ($size === false) {
            return false;
        }

        $type = 'file';

        $result = compact('type', 'path', 'size', 'contents');

        if ($visibility = $config->get('visibility')) {
            $this->setVisibility($path, $visibility);
            $result['visibility'] = $visibility;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function read($path)
    {
        $location = $this->applyPathPrefix($path);

        if ($this->writeFlags & LOCK_EX) {
            $fileHandle = fopen($location, 'rb');

            if ($fileHandle === false) {
                return false;
            }

            flock($fileHandle, LOCK_SH);
        }

        $contents = @file_get_contents($location);

        if ($this->writeFlags & LOCK_EX && ($fileHandle ?? false) !== false) {
            flock($fileHandle, LOCK_UN);
            fclose($fileHandle);
        }

        if ($contents === false) {
            return false;
        }

        return ['type' => 'file', 'path' => $path, 'contents' => $contents];
    }

    /**
     * @inheritdoc
     */
    public function rename($path, $newpath)
    {
        $location = $this->applyPathPrefix($path);
        $destination = $this->applyPathPrefix($newpath);
        $parentDirectory = $this->applyPathPrefix(Util::dirname($newpath));
        $this->ensureDirectory($parentDirectory);

        return rename($location, $destination);
    }

    /**
     * @inheritdoc
     */
    public function copy($path, $newpath)
    {
        $location = $this->applyPathPrefix($path);
        $destination = $this->applyPathPrefix($newpath);
        $this->ensureDirectory(dirname($destination));

        return copy($location, $destination);
    }

    /**
     * @inheritdoc
     */
    public function delete($path)
    {
        $location = $this->applyPathPrefix($path);

        return @unlink($location);
    }

    /**
     * @inheritdoc
     */
    public function listContents($directory = '', $recursive = false)
    {
        $result = [];
        $location = $this->applyPathPrefix($directory);

        if ( ! is_dir($location)) {
            return [];
        }

        $iterator = $recursive ? $this->getRecursiveDirectoryIterator($location) : $this->getDirectoryIterator($location);

        foreach ($iterator as $file) {
            $path = $this->getFilePath($file);

            if (preg_match('#(^|/|\\\\)\.{1,2}$#', $path)) {
                continue;
            }

            $result[] = $this->normalizeFileInfo($file);
        }

        unset($iterator);

        return array_filter($result);
    }

    /**
     * @inheritdoc
     */
    public function getMetadata($path)
    {
        $location = $this->applyPathPrefix($path);
        clearstatcache(false, $location);
        $info = new SplFileInfo($location);

        return $this->normalizeFileInfo($info);
    }

    /**
     * @inheritdoc
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * @inheritdoc
     */
    public function getMimetype($path)
    {
        $location = $this->applyPathPrefix($path);
        $finfo = new Finfo(FILEINFO_MIME_TYPE);
        $mimetype = $finfo->file($location);

        if (in_array($mimetype, ['application/octet-stream', 'inode/x-empty', 'application/x-empty'])) {
            $mimetype = Util\MimeType::detectByFilename($location);
        }

        return ['path' => $path, 'type' => 'file', 'mimetype' => $mimetype];
    }

    /**
     * @inheritdoc
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * @inheritdoc
     */
    public function getVisibility($path)
    {
        $location = $this->applyPathPrefix($path);
        clearstatcache(false, $location);
        $permissions = octdec(substr(sprintf('%o', fileperms($location)), -4));
        $type = is_dir($location) ? 'dir' : 'file';

        foreach ($this->permissionMap[$type] as $visibility => $visibilityPermissions) {
            if ($visibilityPermissions == $permissions) {
                return compact('path', 'visibility');
            }
        }

        $visibility = substr(sprintf('%o', fileperms($location)), -4);

        return compact('path', 'visibility');
    }

    /**
     * @inheritdoc
     */
    public function setVisibility($path, $visibility)
    {
        $location = $this->applyPathPrefix($path);
        $type = is_dir($location) ? 'dir' : 'file';
        $success = chmod($location, $this->permissionMap[$type][$visibility]);

        if ($success === false) {
            return false;
        }

        return compact('path', 'visibility');
    }

    /**
     * @inheritdoc
     */
    public function createDir($dirname, Config $config)
    {
        $location = $this->applyPathPrefix($dirname);
        $umask = umask(0);
        $visibility = $config->get('visibility', 'public');
        $return = ['path' => $dirname, 'type' => 'dir'];

        if ( ! is_dir($location)) {
            if (false === @mkdir($location, $this->permissionMap['dir'][$visibility], true)
                || false === is_dir($location)) {
                $return = false;
            }
        }

        umask($umask);

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function deleteDir($dirname)
    {
        $location = $this->applyPathPrefix($dirname);

        if ( ! is_dir($location)) {
            return false;
        }

        $contents = $this->getRecursiveDirectoryIterator($location, RecursiveIteratorIterator::CHILD_FIRST);

        /** @var SplFileInfo $file */
        foreach ($contents as $file) {
            $this->guardAgainstUnreadableFileInfo($file);
            $this->deleteFileInfoObject($file);
        }

        unset($contents);

        return rmdir($location);
    }

    /**
     * @param SplFileInfo $file
     */
    protected function deleteFileInfoObject(SplFileInfo $file)
    {
        switch ($file->getType()) {
            case 'dir':
                rmdir($file->getRealPath());
                break;
            case 'link':
                unlink($file->getPathname());
                break;
            default:
                unlink($file->getRealPath());
        }
    }

    /**
     * Normalize the file info.
     *
     * @param SplFileInfo $file
     *
     * @return array|void
     *
     * @throws NotSupportedException
     */
    protected function normalizeFileInfo(SplFileInfo $file)
    {
        if ( ! $file->isLink()) {
            return $this->mapFileInfo($file);
        }

        if ($this->linkHandling & self::DISALLOW_LINKS) {
            throw NotSupportedException::forLink($file);
        }
    }

    /**
     * Get the normalized path from a SplFileInfo object.
     *
     * @param SplFileInfo $file
     *
     * @return string
     */
    protected function getFilePath(SplFileInfo $file)
    {
        $location = $file->getPathname();
        $path = $this->removePathPrefix($location);

        return trim(str_replace('\\', '/', $path), '/');
    }

    /**
     * @param string $path
     * @param int    $mode
     *
     * @return RecursiveIteratorIterator
     */
    protected function getRecursiveDirectoryIterator($path, $mode = RecursiveIteratorIterator::SELF_FIRST)
    {
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            $mode
        );
    }

    /**
     * @param string $path
     *
     * @return DirectoryIterator
     */
    protected function getDirectoryIterator($path)
    {
        $iterator = new DirectoryIterator($path);

        return $iterator;
    }

    /**
     * @param SplFileInfo $file
     *
     * @return array
     */
    protected function mapFileInfo(SplFileInfo $file)
    {
        $normalized = [
            'type' => $file->getType(),
            'path' => $this->getFilePath($file),
        ];

        $normalized['timestamp'] = $file->getMTime();

        if ($normalized['type'] === 'file') {
            $normalized['size'] = $file->getSize();
        }

        return $normalized;
    }

    /**
     * @param SplFileInfo $file
     *
     * @throws UnreadableFileException
     */
    protected function guardAgainstUnreadableFileInfo(SplFileInfo $file)
    {
        if ( ! $file->isReadable()) {
            throw UnreadableFileException::forFileInfo($file);
        }
    }
}
