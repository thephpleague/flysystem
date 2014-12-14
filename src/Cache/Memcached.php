<?php

namespace League\Flysystem\Cache;

use Memcached as NativeMemcached;

class Memcached extends AbstractCache
{
    /**
     * @var  string  $key  storage key
     */
    protected $key;

    /**
     * @var  int|null  $expire  seconds until cache expiration
     */
    protected $expire;

    /**
     * @var  \Memcached  $memcached  Memcached instance
     */
    protected $memcached;

    /**
     * Constructor
     *
     * @param \Memcached $memcached
     * @param string     $key       storage key
     * @param int|null   $expire    seconds until cache expiration
     */
    public function __construct(NativeMemcached $memcached, $key = 'flysystem', $expire = null)
    {
        $this->key = $key;
        $this->expire = $expire;
        $this->memcached = $memcached;
    }

    /**
     * {@inheritdoc}
     */
    public function load()
    {
        $contents = $this->memcached->get($this->key);

        if ($contents !== false) {
            $this->setFromStorage($contents);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $contents = $this->getForStorage();
        $expiration = $this->expire === null ? 0 : time() + $this->expire;
        $this->memcached->set($this->key, $contents, $expiration);
    }
}
