<?php

namespace Flysystem\Cache;

class Noop extends AbstractCache
{
    protected $autosave = false;

    public function updateObject($path, array $object, $autosave = false)
    {
        return $object;
    }

    public function isComplete($dirname, $recursive)
    {
        return false;
    }

    public function setComplete($dirname, $recursive)
    {

    }

    public function storeContents($directory, array $contents, $recursive)
    {
        return $contents;
    }

    public function flush()
    {

    }

    public function autosave()
    {

    }

    public function save()
    {

    }

    public function load()
    {

    }

    public function has($path)
    {
        return null;
    }

    public function read($path)
    {
        return false;
    }

    public function listContents($directory = '', $recursive = false)
    {
        return false;
    }

    public function getMetadata($path)
    {
        return false;
    }

    public function getSize($path)
    {
        return false;
    }

    public function getMimetype($path)
    {
        return false;
    }

    public function getTimestamp($path)
    {
        return false;
    }

    public function getVisibility($path)
    {
        return false;
    }

    public function ensureParentDirectories($path)
    {
        return false;
    }
}