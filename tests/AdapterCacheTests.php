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
        $response = ['contents' => json_encode([[], ['' => true], 1234567890]), 'path' => 'file.json'];
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $adapter->shouldReceive('read')->once()->with('file.json')->andReturn($response);
        $adapter->shouldReceive('delete')->once()->with('file.json');
        $cache = new Adapter($adapter, 'file.json', 10);
        $cache->load();
        $this->assertFalse($cache->isComplete('', false));
    }

    public function testLoadSuccess()
    {
        $response = ['contents' => json_encode([[], ['' => true], 9876543210]), 'path' => 'file.json'];
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $adapter->shouldReceive('read')->once()->with('file.json')->andReturn($response);
        $cache = new Adapter($adapter, 'file.json', 10);
        $cache->load();
        $this->assertTrue($cache->isComplete('', false));
    }

    public function testSaveExists()
    {
        $response = json_encode([[], [], null]);
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $adapter->shouldReceive('has')->once()->with('file.json')->andReturn(true);
        $adapter->shouldReceive('update')->once()->with('file.json', $response, Mockery::any());
        $cache = new Adapter($adapter, 'file.json', null);
        $cache->save();
    }

    public function testSaveNew()
    {
        $response = json_encode([[], [], null]);
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $adapter->shouldReceive('has')->once()->with('file.json')->andReturn(false);
        $adapter->shouldReceive('write')->once()->with('file.json', $response, Mockery::any());
        $cache = new Adapter($adapter, 'file.json', null);
        $cache->save();
    }
}
