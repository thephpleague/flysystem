<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Config;
use LogicException;
use ZipArchive;
use League\Flysystem\Util;

class Zip extends AbstractAdapter
{
    protected static $resultMap = array(
        'size'  => 'size',
        'mtime' => 'timestamp',
        'name'  => 'path',
    );

    protected $archive;

    public function __construct($location, ZipArchive $archive = null, $prefix = null)
    {
        $this->setArchive($archive ?: new ZipArchive);
        $this->openArchive($location);
        $this->setPathPrefix($prefix);
    }

    protected function reopenArchive()
    {
        $path = $this->archive->filename;
        $this->archive->close();
        $this->openArchive($path);
    }

    public function setArchive(ZipArchive $archive)
    {
        $this->archive = $archive;
    }

    public function getArchive()
    {
        return $this->archive;
    }

    public function openArchive($location)
    {
        $location = str_replace('/', DIRECTORY_SEPARATOR, $location);

        if (($response = $this->archive->open($location, ZipArchive::CREATE)) !== true) {
            throw new LogicException('Could not open zip archive at:'.$location.', error: '.$response);
        }
    }

    public function write($path, $contents, $config = null)
    {
        $location = $this->applyPathPrefix($path);
        $dirname = Util::dirname($path);
        $config = Util::ensureConfig($config);

        if ( ! empty($dirname) && ! $this->has($dirname)) {
            $this->createDir($dirname);
        }

        if ( ! $this->archive->addFromString($location, $contents)) {
            return false;
        }

        $result = compact('path', 'contents');

        if ($config && $config->get('visibility')) {
            throw new LogicException(get_class($this).' does not support visibility settings.');
        }

        return $result;
    }

    public function update($path, $contents, $config = null)
    {
        $this->delete($path);

        return $this->write($path, $contents, $config);
    }

    public function rename($path, $newpath)
    {
        $source = $this->applyPathPrefix($path);
        $destination = $this->applyPathPrefix($newpath);

        return $this->archive->renameName($source, $destination);
    }

    public function delete($path)
    {
        $location = $this->applyPathPrefix($path);

        return $this->archive->deleteName($location);
    }

    public function deleteDir($dirname)
    {
        // This is needed to ensure the right number of
        // files are set to the $numFiles property.
        $this->reopenArchive();

        $location = $this->applyPathPrefix($dirname);
        $path = Util::normalizePrefix($location, '/');
        $length = strlen($path);

        for ($i = 0; $i < $this->archive->numFiles; $i++) {
            $info = $this->archive->statIndex($i);

            if (substr($info['name'], 0, $length) === $path) {
                $this->archive->deleteIndex($i);
            }
        }

        return $this->archive->deleteName($dirname);
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
        if ( ! $this->has($dirname)) {
            $location = $this->applyPathPrefix($dirname);

            $this->archive->addEmptyDir($location);
        }

        return array('path' => $dirname);
    }

    public function has($path)
    {
        return $this->getMetadata($path);
    }

    public function read($path)
    {
        $this->reopenArchive();
        $location = $this->applyPathPrefix($path);

        if ( ! $contents = $this->archive->getFromName($location)) {
            return false;
        }

        return compact('contents');
    }

    public function readStream($path)
    {
        $this->reopenArchive();
        $location = $this->applyPathPrefix($path);

        if ( ! $stream = $this->archive->getStream($location)) {
            return false;
        }

        return compact('stream');
    }

    public function listContents($dirname = '', $recursive = false)
    {
        $result = array();

        // This is needed to ensure the right number of
        // files are set to the $numFiles property.
        $this->reopenArchive();

        for ($i = 0; $i < $this->archive->numFiles; $i++) {
            if ($info = $this->archive->statIndex($i)) {
                $result[] = $this->normalizeObject($info);
            }
        }

        return $result;
    }

    public function getMetadata($path)
    {
        $location = $this->applyPathPrefix($path);

        if ( ! $info = $this->archive->statName($location)) {
            return false;
        }

        return $this->normalizeObject($info);
    }

    protected function normalizeObject(array $object)
    {
        if (substr($object['name'], -1) === '/') {
            return array(
                'path' => $this->removePathPrefix(trim($object['name'], '/')),
                'type' => 'dir'
            );
        }

        $result = array('type' => 'file');
        $normalised = Util::map($object, static::$resultMap);
        $normalised['path'] = $this->removePathPrefix($normalised['path']);

        return array_merge($result, $normalised);
    }

    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    public function getMimetype($path)
    {
        if ( ! $data = $this->read($path)) {
            return false;
        }

        $data['mimetype'] = Util::guessMimeType($path, $data['contents']);

        return $data;
    }

    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }
}
