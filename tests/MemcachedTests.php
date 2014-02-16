<?php

use League\Flysystem\Cache\Memcached;
use Predis\Client;

class MemcachedTests extends PHPUnit_Framework_TestCase
{
    protected function getMemcachedClient()
    {
        if (defined('HHVM_VERSION')) {
            return $this->markTestSkipped('This memcached test is broken on HHVM.');
        }

        return Mockery::mock('Memcached');
    }

    public function testLoadFail()
    {
        $client = $this->getMemcachedClient();
        $client->shouldReceive('get')->once()->andReturn(null);
        $cache = new Memcached($client);
        $cache->load();
        $this->assertFalse($cache->isComplete('', false));
    }

    public function testLoadSuccess()
    {
        $response = json_encode(array(array(), array('' => true)));
        $client = $this->getMemcachedClient();
        $client->shouldReceive('get')->once()->andReturn($response);
        $cache = new Memcached($client);
        $cache->load();
        $this->assertTrue($cache->isComplete('', false));
    }

    public function testSave()
    {
        $response = json_encode(array(array(), array()));
        $client = $this->getMemcachedClient();
        $client->shouldReceive('set')->once()->andReturn($response);
        $cache = new Memcached($client);
        $cache->save();
    }
}
