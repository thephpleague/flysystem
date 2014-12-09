<?php

namespace League\Flysystem\Cache;

use League\Flysystem\Util;

class Noop extends AbstractCache
{
    /**
     * {@inheritdoc}
     */
    protected $autosave = false;

    /**
     * {@inheritdoc}
     */
    public function updateObject($path, array $object, $autosave = false)
    {
        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function isComplete($dirname, $recursive)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setComplete($dirname, $recursive)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function storeContents($directory, array $contents, $recursive)
    {
        if ($recursive) {
            return $contents;
        }

        foreach ($contents as $index => $object) {
            $pathinfo = Util::pathinfo($object['path']);
            $object = array_merge($pathinfo, $object);

            if (! $recursive && $object['dirname'] !== $directory) {
                unset($contents[$index]);
                continue;
            }

            $contents[$index] = $object;
        }

        return $contents;
    }

    /**
     * {@inheritdoc}
     */
    public function storeMiss($path)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function autosave()
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function load()
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility($path)
    {
        return false;
    }
}
