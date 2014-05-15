<?php

namespace League\Flysystem\Cache;

use Predis\Client;

class Predis extends AbstractCache
{
    /**
     * @var  \Predis\Client  $client  Predis Client
     */
    protected $client;

    /**
     * @var  string  $key  storage key
     */
    protected $key;

    /**
     * @var  int|null  $expire  seconds until cache expiration
     */
    protected $expire;

    /**
     * Constructor
     *
     * @param \Predis\Client  $client  predis client
     * @param string          $key     storage key
     * @param int|null        $expire  seconds until cache expiration
     */
    public function __construct(Client $client = null, $key = 'flysystem', $expire = null)
    {
        $this->client = $client ?: new Client;
        $this->key = $key;
        $this->expire = $expire;
    }

    /**
     * {@inheritdoc}
     */
    public function load()
    {
        if (($contents = $this->client->get($this->key)) !== null) {
            $this->setFromStorage($contents);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $contents = $this->getForStorage();
        $this->client->set($this->key, $contents);

        if ($this->expire !== null) {
            $this->client->expire($this->key, $this->expire);
        }
    }
}
