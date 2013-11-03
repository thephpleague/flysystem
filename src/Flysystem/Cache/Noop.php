<?php

namespace Flysystem\Cache;

class Noop extends AbstractCache
{
	protected $autosave = false;

	public function updateObject($path, array $object, $autosave = false)
	{
		return $object;
	}

    public function isComplete()
    {
    	return false;
    }

    public function setComplete($complete = true)
    {

    }

    public function storeContents(array $contents)
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
    	return false;
    }

    public function read($path)
    {
    	return false;
    }

    public function listContents()
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