<?php

namespace Flysystem\Cache;

use Flysystem\CacheInterface;
use Flysystem\Util;

abstract class AbstractCache implements CacheInterface
{
    /**
     * @var  boolean  $autosave
     */
    protected $autosave = true;

    /**
     * @var  boolean  @complete
     */
    protected $complete = false;

    /**
     * @var  array  $cache
     */
    protected $cache = array();

    /**
     * Destructor
     */
    public function __destruct()
    {
        if ( ! $this->autosave) {
            $this->save();
        }
    }

    /**
     * Get the autosave setting
     *
     * @return  boolean  autosave
     */
    public function getAutosave()
    {
        return $this->autosave;
    }

    /**
     * Get the autosave setting
     *
     * @param   boolean  $autosave
     * @return  $this
     */
    public function setAutosave($autosave)
    {
        $this->autosave = $autosave;

        return $this;
    }

    /**
     * Store the contents listing
     *
     * @param   array  $contents
     * @return  array  contents listing
     */
    public function storeContents(array $contents)
    {
        foreach ($contents as $object) {
            $this->updateObject($object['path'], $object);
        }

        $this->setComplete();

        return $this->listContents();
    }

    /**
     * Opdate the metadata for an object
     *
     * @param   string   $path      object path
     * @param   array    $object    object metadata
     * @param   boolean  $autosave  wether to trigger the autosave routine
     */
    public function updateObject($path, array $object, $autosave = false)
    {
        if ( ! isset($this->cache[$path])) {
            $this->cache[$path] = Util::pathinfo($path);
        }

        $this->cache[$path] = array_merge($this->cache[$path], $object);

        if ($autosave) {
            $this->autosave();
        }

        return $this->cache[$path];
    }

    /**
     * Get the contents listing
     *
     * @return  array  contents listing
     */
    public function listContents()
    {
        return array_values($this->cache);
    }

    /**
     * Check wether an object has been cached
     *
     * @return  boolean  cached boolean
     */
    public function has($path)
    {
        return isset($this->cache[$path]);
    }

    /**
     * Retreive the contents of an object
     *
     * @return  null|string  contents or null on failure
     */
    public function read($path)
    {
        if (isset($this->cache[$path]['contents'])) {
            return $this->cache[$path]['contents'];
        }
    }

    /**
     * Rename an object
     *
     * @param  string  $path
     * @param  string  $newpath
     */
    public function rename($path, $newpath)
    {
        if ( ! isset($this->cache[$path])) {
            return false;
        }

        $object = $this->cache[$path];
        unset($this->cache[$path]);
        $object['path'] = $newpath;
        $object = array_merge($object, Util::pathinfo($newpath));
        $this->cache[$newpath] = $object;

        $this->autosave();
    }

    /**
     * Delete an object from cache
     *
     * @param  string  $path  object path
     */
    public function delete($path)
    {
        if (isset($this->cache[$path])) {
            unset($this->cache[$path]);
        }

        $this->autosave();
    }

    /**
     * Delete a directory from cache and all its siblings
     *
     * @param  string  $dirname  object path
     */
    public function deleteDir($dirname)
    {
        foreach ($this->cache as $path => $object) {
            if (strpos($path, $dirname) === 0) {
                unset($this->cache[$path]);
            }
        }

        $this->autosave();
    }

    /**
     * Retreive the mimetype of an object
     *
     * @return  null|string  mimetype or null on failure
     */
    public function getMimetype($path)
    {
        if (isset($this->cache[$path]['mimetype'])) {
            return $this->cache[$path]['mimetype'];
        }

        if ( ! $contents = $this->read($path)) {
            return null;
        }

        $mimetype = Util::contentMimetype($contents);
        $this->cache[$path]['mimetype'] = $mimetype;

        return $mimetype;
    }

    /**
     * Retreive the size of an object
     *
     * @return  null|string  size or null on failure
     */
    public function getSize($path)
    {
        if (isset($this->cache[$path]['size'])) {
            return $this->cache[$path]['size'];
        }
    }

    /**
     * Retreive the timestamp of an object
     *
     * @return  null|integer  timestamp or null on failure
     */
    public function getTimestamp($path)
    {
        if (isset($this->cache[$path]['timestamp'])) {
            return $this->cache[$path]['timestamp'];
        }
    }

    /**
     * Retreive the visiility of an object
     *
     * @return  null|string  visiility or null on failure
     */
    public function getVisibility($path)
    {
        if (isset($this->cache[$path]['visibility'])) {
            return $this->cache[$path]['visibility'];
        }
    }

    /**
     * Retreive the metadata of an object
     *
     * @return  null|array  metadata or null on failure
     */
    public function getMetadata($path)
    {
        if (isset($this->cache[$path]['type'])) {
            return $this->cache[$path];
        }
    }

    /**
     * Check wether the listing is complete
     *
     * @return boolean
     */
    public function isComplete()
    {
        return $this->complete;
    }

    /**
     * Set the cache to (in)complete
     *
     * @param   boolean  wether the listing is complete
     * @return  $this
     */
    public function setComplete($complete = true)
    {
        $this->complete = $complete;
        $this->autosave();

        return $this;
    }

    /**
     * Filter the contents from a listing
     *
     * @param   array  $contents  object listing
     * @return  array  filtered contents
     */
    public function cleanContents(array $contents)
    {
        foreach ($contents as $path => $object) {
            if (isset($object['contents'])) {
                unset($contents[$path]['contents']);
            }
        }

        return $contents;
    }

    /**
     * Flush the cache
     */
    public function flush()
    {
        $this->cache = array();
        $this->setComplete(false);
        $this->autosave();
    }

    /**
     * Trigger autosaving
     */
    public function autosave()
    {
        if ($this->autosave) {
            $this->save();
        }
    }

    /**
     * Retreive serialied cache data
     *
     * @return  string  serialized data
     */
    public function getForStorage()
    {
        $cleaned = $this->cleanContents($this->cache);

        return json_encode(array($this->complete, $cleaned));
    }

    /**
     * Load from serialized cache data
     *
     * @param  string  $json
     */
    public function setFromStorage($json)
    {
        list($complete, $cache) = json_decode($json, true);
        $this->complete = $complete;
        $this->cache = $cache;
    }

    /**
     * Ensure parent directories of an object
     *
     * @param   string  $path  object path
     */
    public function ensureParentDirectories($path)
    {
        $object = $this->cache[$path];

        while ($object['dirname'] !== '' and ! isset($this->cache[$object['dirname']])) {
            $object = Util::pathinfo($object['dirname']);
            $this->cache[$object['path']] = $object;
        }
    }
}
