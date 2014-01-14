<?php

use League\Flysystem\Cache\Predis;
use Predis\Client;

class PredisTests extends PHPUnit_Framework_TestCase
{
    public function testLoadFail()
    {
        $client = Mockery::mock('Predis\Client');
        $client->shouldReceive('get')->once()->andReturn(null);
        $cache = new Predis($client);
        $cache->load();
        $this->assertFalse($cache->isComplete('', false));
    }

    public function testLoadSuccess()
    {
        $response = json_encode(array(array(), array('' => true)));
        $client = Mockery::mock('Predis\Client');
        $client->shouldReceive('get')->once()->andReturn($response);
        $cache = new Predis($client);
        $cache->load();
        $this->assertTrue($cache->isComplete('', false));
    }

    public function testSave()
    {
        $response = json_encode(array(array(), array()));
        $client = Mockery::mock('Predis\Client');
        $client->shouldReceive('set')->once()->andReturn($response);
        $cache = new Predis($client);
        $cache->save();
    }

    public function testSaveWithExpire()
    {
        $response = json_encode(array(array(), array()));
        $client = Mockery::mock('Predis\Client');
        $client->shouldReceive('set')->once()->andReturn($response);
        $client->shouldReceive('expire')->once();
        $cache = new Predis($client, 'flysystem', 20);
        $cache->save();
    }
}
