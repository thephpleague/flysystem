<?php

namespace League\Flysystem\Cache;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

class Adapter extends AbstractCache
{
    /**
     * @var  AdapterInterface  $adapter  An adapter
     */
    protected $adapter;

    /**
     * @var  string  $file  the file to cache to
     */
    protected $file;

    /**
     * @var  int|null  $expire  seconds until cache expiration
     */
    protected $expire = null;

    /**
     * Constructor
     *
     * @param AdapterInterface $adapter adapter
     * @param string           $file    the file to cache to
     * @param int|null         $expire  seconds until cache expiration
     */
    public function __construct(AdapterInterface $adapter, $file, $expire = null)
    {
        $this->adapter = $adapter;
        $this->file = $file;
        $this->setExpire($expire);
    }

    /**
     * Set the expiration time in seconds
     *
     * @param int $expire relative expiration time
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
     * @param int $time relative expiration time
     *
     * @return int actual expiration time
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
        list($cache, $complete, $expire) = json_decode($json, true);

        if (! $expire || $expire > $this->getTime()) {
            $this->cache = $cache;
            $this->complete = $complete;
        } else {
            $this->adapter->delete($this->file);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load()
    {
        if (($file = $this->adapter->read($this->file)) !== null) {
            $this->setFromStorage($file['contents']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getForStorage()
    {
        $cleaned = $this->cleanContents($this->cache);

        return json_encode([$cleaned, $this->complete, $this->expire]);
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $config = new Config();
        $contents = $this->getForStorage();

        if ($this->adapter->has($this->file)) {
            $this->adapter->update($this->file, $contents, $config);
        } else {
            $this->adapter->write($this->file, $contents, $config);
        }
    }
}
