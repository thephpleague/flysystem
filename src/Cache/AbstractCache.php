<?php

namespace League\Flysystem\Cache;

use League\Flysystem\CacheInterface;
use League\Flysystem\Util;

abstract class AbstractCache implements CacheInterface
{
    /**
     * @var  boolean  $autosave
     */
    protected $autosave = true;

    /**
     * @var  array  $cache
     */
    protected $cache = array();

    /**
     * @var  array  $complete
     */
    protected $complete = array();

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
     * @param   string   $directory
     * @param   array    $contents
     * @param   boolean  $recursive
     * @return  array  contents listing
     */
    public function storeContents($directory, array $contents, $recursive = false)
    {
        $directories = array($directory);

        foreach ($contents as $index => $object) {
            $object = $this->updateObject($object['path'], $object);
            $contents[$index] = $object;

            if ( ! empty($directory) && strpos($object['path'], $directory) === false) {
                unset($contents[$index]);
                continue;
            }

            if ($recursive && ! in_array($object['dirname'], $directories)) {
                $directories[] = $object['dirname'];
            }

            if ($recursive === false && $object['dirname'] !== $directory) {
                unset($contents[$index]);
            }
        }

        foreach ($directories as $directory)
            $this->setComplete($directory, $recursive);

        $this->autosave();

        return array_values($contents);
    }

    /**
     * Update the metadata for an object
     *
     * @param   string   $path      object path
     * @param   array    $object    object metadata
     * @param   boolean  $autosave  whether to trigger the autosave routine
     */
    public function updateObject($path, array $object, $autosave = false)
    {
        if ( ! $this->has($path)) {
            $this->cache[$path] = Util::pathinfo($path);
        }

        $this->cache[$path] = array_merge($this->cache[$path], $object);

        if ($autosave) {
            $this->autosave();
        }

        $this->ensureParentDirectories($path);

        return $this->cache[$path];
    }

    /**
     * Store object hit miss
     *
     * @param   string   $path
     * @return  $this
     */
    public function storeMiss($path)
    {
        $this->cache[$path] = false;
        $this->autosave();

        return $this;
    }

    /**
     * Get the contents listing
     *
     * @param   string   $dirname
     * @param   boolean  $recursive
     * @return  array    contents listing
     */
    public function listContents($dirname = '', $recursive = false)
    {
        $result = array();

        foreach ($this->cache as $object) {
            if ($object['dirname'] !== $dirname) {
                continue;
            }

            $result[] = $object;

            if ($recursive && $object['type'] === 'dir') {
                $result = array_merge($result, $this->listContents($object['path'], true));
            }
        }

        return $result;
    }

    /**
     * Check whether an object has been cached
     *
     * @param   string   $path
     * @return  boolean  cached boolean
     */
    public function has($path)
    {
        if ( ! isset($this->cache[$path])) {
            $dirname = Util::dirname($path);

            return $this->isComplete($dirname, false) ? false : null;
        }

        return $this->cache[$path] !== false;
    }

    /**
     * Retrieve the contents of an object
     *
     * @param   string       $path
     * @return  false|string  contents or null on failure
     */
    public function read($path)
    {
        if (isset($this->cache[$path]['contents'])) {
            return $this->cache[$path]['contents'];
        }

        return false;
    }

    /**
     * Retrieve the contents of an object
     *
     * @param   string       $path
     * @return  false|string  contents or null on failure
     */
    public function readStream($path)
    {
        if ( ! isset($this->cache[$path]['stream'])) {
            return false;
        }

        if ( ! is_resource($this->cache[$path]['stream'])) {
            return false;
        }

        return $this->cache[$path]['stream'];
    }

    /**
     * Rename an object
     *
     * @param  string  $path
     * @param  string  $newpath
     */
    public function rename($path, $newpath)
    {
        if ( ! $this->has($path)) return false;

        $object = $this->cache[$path];
        unset($this->cache[$path]);
        $object['path'] = $newpath;
        $object = array_merge($object, Util::pathinfo($newpath));
        $this->cache[$newpath] = $object;

        $this->autosave();
    }

    /**
     * Copy an object
     *
     * @param  string  $path
     * @param  string  $newpath
     */
    public function copy($path, $newpath)
    {
        if ( ! $this->has($path)) {
            return false;
        }

        $object = $this->cache[$path];
        $object = array_merge($object, Util::pathinfo($newpath));

        return $this->updateObject($newpath, $object, true);
    }

    /**
     * Delete an object from cache
     *
     * @param   string  $path  object path
     * @return  $this
     */
    public function delete($path)
    {
        if (isset($this->cache[$path])) {
            unset($this->cache[$path]);
        }

        $this->autosave();

        return $this;
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

        if (isset($this->complete[$dirname])) {
            unset($this->complete[$dirname]);
        }

        $this->autosave();
    }

    /**
     * Retrieve the mime-type of an object
     *
     * @param   string       $path
     * @return  null|string  mime-type or null on failure
     */
    public function getMimetype($path)
    {
        if (isset($this->cache[$path]['mimetype'])) {
            return $this->cache[$path]['mimetype'];
        }

        if ( ! $contents = $this->read($path)) {
            return false;
        }

        $mimetype = Util::guessMimeType($path, $contents);
        $this->cache[$path]['mimetype'] = $mimetype;

        return $mimetype;
    }

    /**
     * Retrieve the size of an object
     *
     * @param   string       $path
     * @return  false|string  size or null on failure
     */
    public function getSize($path)
    {
        if (isset($this->cache[$path]['size'])) {
            return $this->cache[$path]['size'];
        }

        return false;
    }

    /**
     * Retrieve the timestamp of an object
     *
     * @param   string        $path
     * @return  null|integer  timestamp or null on failure
     */
    public function getTimestamp($path)
    {
        if (isset($this->cache[$path]['timestamp'])) {
            return $this->cache[$path]['timestamp'];
        }
    }

    /**
     * Retrieve the visibility of an object
     *
     * @param   string       $path
     * @return  null|string  visibility or null on failure
     */
    public function getVisibility($path)
    {
        if (isset($this->cache[$path]['visibility'])) {
            return $this->cache[$path]['visibility'];
        }
    }

    /**
     * Retrieve the metadata of an object
     *
     * @param   string      $path
     * @return  null|array  metadata or null on failure
     */
    public function getMetadata($path)
    {
        if (isset($this->cache[$path]['type'])) {
            return $this->cache[$path];
        }
    }

    /**
     * Check whether the listing is complete
     *
     * @param  string  $dirname
     * @param  boolean $recursive
     * @return boolean
     */
    public function isComplete($dirname, $recursive)
    {
        if ( ! array_key_exists($dirname, $this->complete)) {
            return false;
        }

        if ($recursive && $this->complete[$dirname] !== 'recursive') {
            return false;
        }

        return true;
    }

    /**
     * Set the cache to complete
     *
     * @param   string   $dirname
     * @param   boolean  $recursive
     * @return  $this
     */
    public function setComplete($dirname, $recursive)
    {
        $this->complete[$dirname] = $recursive ? 'recursive' : true;

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
        $this->complete = array();
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
     * Retrieve serialized cache data
     *
     * @return  string  serialized data
     */
    public function getForStorage()
    {
        $cleaned = $this->cleanContents($this->cache);

        return json_encode(array($cleaned, $this->complete));
    }

    /**
     * Load from serialized cache data
     *
     * @param  string  $json
     */
    public function setFromStorage($json)
    {
        list ($cache, $complete) = json_decode($json, true);
        $this->cache = $cache;
        $this->complete = $complete;
    }

    /**
     * Ensure parent directories of an object
     *
     * @param   string  $path  object path
     */
    public function ensureParentDirectories($path)
    {
        $object = $this->cache[$path];

        while ($object['dirname'] !== '' && ! isset($this->cache[$object['dirname']])) {
            $object = Util::pathinfo($object['dirname']);
            $object['type'] = 'dir';
            $this->cache[$object['path']] = $object;
        }
    }
}
