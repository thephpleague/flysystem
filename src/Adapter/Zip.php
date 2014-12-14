<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Adapter\Polyfill\StreamedCopyTrait;
use League\Flysystem\Adapter\Polyfill\StreamedWritingTrait;
use League\Flysystem\Config;
use League\Flysystem\Util;
use LogicException;
use ZipArchive;

class Zip extends AbstractAdapter
{
    use StreamedWritingTrait;
    use StreamedCopyTrait;
    use NotSupportingVisibilityTrait;

    /**
     * @var array
     */
    protected static $resultMap = [
        'size'  => 'size',
        'mtime' => 'timestamp',
        'name'  => 'path',
    ];

    /**
     * @var ZipArchive
     */
    protected $archive;

    /**
     * @param            $location
     * @param ZipArchive $archive
     * @param null       $prefix
     */
    public function __construct($location, ZipArchive $archive = null, $prefix = null)
    {
        $this->setArchive($archive ?: new ZipArchive());
        $this->openArchive($location);
        $this->setPathPrefix($prefix);
    }

    /**
     * Re-open an archive to ensure persistence.
     */
    protected function reopenArchive()
    {
        $path = $this->archive->filename;
        $this->archive->close();
        $this->openArchive($path);
    }

    /**
     * ZipArchive setter
     *
     * @param ZipArchive $archive
     */
    public function setArchive(ZipArchive $archive)
    {
        $this->archive = $archive;
    }

    /**
     * Get the used ZipArchive
     *
     * @return ZipArchive
     */
    public function getArchive()
    {
        return $this->archive;
    }

    /**
     * Open a zip file.
     *
     * @param $location
     */
    public function openArchive($location)
    {
        $location = str_replace('/', DIRECTORY_SEPARATOR, $location);

        if (($response = $this->archive->open($location, ZipArchive::CREATE)) !== true) {
            throw new LogicException('Could not open zip archive at:'.$location.', error: '.$response);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        $dirname = Util::dirname($path);

        if (! empty($dirname) && ! $this->has($dirname)) {
            $this->createDir($dirname, $config);
        }

        if (! $this->archive->addFromString($location, $contents)) {
            return false;
        }

        $result = compact('path', 'contents');

        if ($config && $config->get('visibility')) {
            throw new LogicException(get_class($this).' does not support visibility settings.');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        $this->delete($path);

        return $this->write($path, $contents, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {
        $source = $this->applyPathPrefix($path);
        $destination = $this->applyPathPrefix($newpath);

        return $this->archive->renameName($source, $destination);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        $location = $this->applyPathPrefix($path);

        return $this->archive->deleteName($location);
    }

    /**
     * {@inheritdoc}
     */
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
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        if (! $this->has($dirname)) {
            $location = $this->applyPathPrefix($dirname);
            $this->archive->addEmptyDir($location);
        }

        return ['path' => $dirname];
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        $this->reopenArchive();
        $location = $this->applyPathPrefix($path);

        if (! $contents = $this->archive->getFromName($location)) {
            return false;
        }

        return compact('contents');
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        $this->reopenArchive();
        $location = $this->applyPathPrefix($path);

        if (! $stream = $this->archive->getStream($location)) {
            return false;
        }

        return compact('stream');
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($dirname = '', $recursive = false)
    {
        $result = [];

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

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        $location = $this->applyPathPrefix($path);

        if (! $info = $this->archive->statName($location)) {
            return false;
        }

        return $this->normalizeObject($info);
    }

    /**
     * Normalize a zip response array
     *
     * @param array $object
     *
     * @return array
     */
    protected function normalizeObject(array $object)
    {
        if (substr($object['name'], -1) === '/') {
            return [
                'path' => $this->removePathPrefix(trim($object['name'], '/')),
                'type' => 'dir',
            ];
        }

        $result = ['type' => 'file'];
        $normalised = Util::map($object, static::$resultMap);
        $normalised['path'] = $this->removePathPrefix($normalised['path']);

        return array_merge($result, $normalised);
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        if (! $data = $this->read($path)) {
            return false;
        }

        $data['mimetype'] = Util::guessMimeType($path, $data['contents']);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }
}
