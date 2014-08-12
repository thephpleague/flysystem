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
        if (($contents = $this->executeCommand('get', array($this->key))) !== null) {
            $this->setFromStorage($contents);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $contents = $this->getForStorage();
        $this->executeCommand('set', array($this->key, $contents));

        if ($this->expire !== null) {
            $this->executeCommand('expire', array($this->key, $this->expire));
        }
    }

    /**
     * Execute a Predis command
     *
     * @param   string  $name
     * @param   array   $arguments
     * @return  mixed
     */
    protected function executeCommand($name, array $arguments)
    {
        $command = $this->client->createCommand($name, $arguments);

        return $this->client->executeCommand($command);
    }
}
