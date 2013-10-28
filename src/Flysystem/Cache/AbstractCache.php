<?php

namespace Flysystem\Cache;

use Flysystem\CacheInterface;
use Flysystem\Util;

abstract class AbstractCache implements CacheInterface
{
    protected $autosave = true;
    protected $complete = false;
    protected $cache = array();

    public function __destruct()
    {
        if ( ! $this->autosave) {
            $this->save();
        }
    }

    public function getAutosave()
    {
        return $this->autosave;
    }

    public function setAutosave($autosave)
    {
        $this->autosave = $autosave;

        return $this;
    }

    public function storeContents(array $contents)
    {
        foreach ($contents as $object) {
            $this->updateObject($object['path'], $object);
        }

        $this->setComplete();

        return $this->listContents();
    }

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

    public function listContents()
    {
        return array_values($this->cache);
    }

    public function has($path)
    {
        return isset($this->cache[$path]);
    }

    public function read($path)
    {
        if (isset($this->cache[$path]['contents'])) {
            return $this->cache[$path]['contents'];
        }
    }

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

    public function delete($path)
    {
        if (isset($this->cache[$path])) {
            unset($this->cache[$path]);
        }

        $this->autosave();
    }

    public function deleteDir($dirname)
    {
        foreach ($this->cache as $path => $object) {
            if (strpos($path, $dirname) === 0) {
                unset($this->cache[$path]);
            }
        }

        $this->autosave();
    }

    public function getMimetype($path)
    {
        if (isset($this->cache[$path]['mimetype'])) {
            return $this->cache[$path]['mimetype'];
        }

        if ( ! $contents = $this->read($path)) {
            return false;
        }

        $mimetype = Util::contentMimetype($contents);
        $this->cache[$path]['mimetype'] = $mimetype;

        return $mimetype;
    }

    public function getSize($path)
    {
        if (isset($this->cache[$path]['size'])) {
            return $this->cache[$path]['size'];
        }
    }

    public function getTimestamp($path)
    {
        if (isset($this->cache[$path]['timestamp'])) {
            return $this->cache[$path]['timestamp'];
        }
    }

    public function getVisibility($path)
    {
        if (isset($this->cache[$path]['visibility'])) {
            return $this->cache[$path]['visibility'];
        }
    }

    public function getMetadata($path)
    {
        if (isset($this->cache[$path]['type'])) {
            return $this->cache[$path];
        }
    }

    public function isComplete()
    {
        return $this->complete;
    }

    public function setComplete($complete = true)
    {
        $this->complete = $complete;
        $this->autosave();

        return $this;
    }

    public function cleanContents(array $contents)
    {
        foreach ($contents as $path => $object) {
            if (isset($object['contents'])) {
                unset($contents[$path]['contents']);
            }
        }

        return $contents;
    }

    public function flush()
    {
        $this->cache = array();
        $this->setComplete(false);
        $this->autosave();
    }

    public function autosave()
    {
        if ($this->autosave) {
            $this->save();
        }
    }

    public function getForStorage()
    {
        $cleaned = $this->cleanContents($this->cache);

        return json_encode([$this->complete, $cleaned]);
    }

    public function setFromStorage($serialized)
    {
        list($complete, $cache) = json_decode($serialized, true);
        $this->complete = $complete;
        $this->cache = $cache;
    }

    public function ensureParentDirectories($path)
    {
        $object = $this->cache[$path];

        while ($object['dirname'] !== '' and ! isset($this->cache[$object['dirname']])) {
            $object = Util::pathinfo($object['dirname']);
            $this->cache[$object['path']] = $object;
        }
    }
}
