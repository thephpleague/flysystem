<?php

namespace League\Flysystem\Cache;

use League\Flysystem\AdapterInterface;

class Adapter extends AbstractCache
{
    /**
     * @var  AdapterInterface  $adapter  An adapter
     */
    protected $adapter;

    /**
     * @var  string  $key  storage key
     */
    protected $key;

    /**
     * @var  int|null  $expire  seconds until cache expiration
     */
    protected $expire = null;

    /**
     * Constructor
     *
     * @param AdapterInterface  $adapter  adapter
     * @param string            $key      storage key
     * @param int|null          $expire   seconds until cache expiration
     */
    public function __construct(AdapterInterface $adapter, $key = 'flysystem', $expire = null)
    {
        $this->adapter = $adapter;
        $this->key = $key;
        $this->setExpire($expire);
    }

    /**
     * Set the expiration time in seconds
     *
     * @param  int  $expire  relative expiration time
     */
    protected function setExpire($expire)
    {
        if ($expire) {
            $this->expire = $this->getTime($expire);
        }
    }

    /**
     * Get expiration time in seconds
     *
     * @param  int  $time  relative expiration time
     * @return int  actual expiration time
     */
    protected function getTime($time = 0)
    {
        return intval(microtime(true)) + $time;
    }

    /**
     * {@inheritdoc}
     */
    public function setFromStorage($json)
    {
        list ($cache, $complete, $expire) = json_decode($json, true);

        if ( ! $expire || $expire > $this->getTime()) {
            $this->cache = $cache;
            $this->complete = $complete;
        } else {
            $this->adapter->delete($this->key);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load()
    {
        $contents = $this->adapter->read($this->key);

        if ($contents) {
            $this->setFromStorage($contents);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getForStorage()
    {
        $cleaned = $this->cleanContents($this->cache);

        return json_encode(array($cleaned, $this->complete, $this->expire));
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $contents = $this->getForStorage();

        $this->adapter->put($this->key, $contents);
    }
}
