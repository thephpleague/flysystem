<?php

namespace Flysystem\Cache;

use Memcached as NativeMemcached;

class Memcached extends AbstractCache
{
    protected $key;
    protected $expire;
    protected $memcached;

    public function __construct(NativeMemcached $memcached, $key = 'flysystem', $expire = null)
    {
        $this->key = $key;
        $this->expire = $expire;
        $this->memcached = $memcached;
    }

    public function load()
    {
        $contents = $this->memcached->get($this->key);

        if ($contents) {
            $this->setFromStorage($contents);
        }
    }

    public function save()
    {
        $contents = $this->getForStorage();
        $expiration = $this->expire === null ? 0 : time() + $this->expire;
        $this->memcached->set($this->key, $contents, $expiration);
    }
}