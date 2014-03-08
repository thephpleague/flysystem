<?php

use League\Flysystem\Cache\Adapter;

class AdapterCacheTests extends PHPUnit_Framework_TestCase
{
    public function testLoadFail()
    {
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $adapter->shouldReceive('read')->once()->with('file.json')->andReturn(null);
        $cache = new Adapter($adapter, 'file.json', 10);
        $cache->load();
        $this->assertFalse($cache->isComplete('', false));
    }

    public function testLoadExpired()
    {
        $response = json_encode(array(array(), array('' => true), 1234567890));
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $adapter->shouldReceive('read')->once()->with('file.json')->andReturn($response);
        $adapter->shouldReceive('delete')->once()->with('file.json');
        $cache = new Adapter($adapter, 'file.json', 10);
        $cache->load();
        $this->assertFalse($cache->isComplete('', false));
    }

    public function testLoadSuccess()
    {
        $response = json_encode(array(array(), array('' => true), 9876543210));
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $adapter->shouldReceive('read')->once()->with('file.json')->andReturn($response);
        $cache = new Adapter($adapter, 'file.json', 10);
        $cache->load();
        $this->assertTrue($cache->isComplete('', false));
    }

    public function testSave()
    {
        $response = json_encode(array(array(), array(), null));
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $adapter->shouldReceive('put')->once()->with('file.json', $response)->andReturn($response);
        $cache = new Adapter($adapter, 'file.json', null);
        $cache->save();
    }
}
