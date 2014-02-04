<?php

use League\Flysystem\Filesystem;

class FlysystemStreamTests extends PHPUnit_Framework_TestCase
{
    public function testWriteStream()
    {
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $adapter->shouldReceive('has')->andReturn(false);
        $adapter->shouldReceive('writeStream')->andReturn(array('path' => 'file.txt'), false);
        $filesystem = new Filesystem($adapter);
        $this->assertTrue($filesystem->writeStream('file.txt', tmpfile()));
        $filesystem->flushCache();
        $this->assertFalse($filesystem->writeStream('file.txt', tmpfile()));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWriteStreamFail()
    {
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $adapter->shouldReceive('has')->andReturn(false);
        $filesystem = new Filesystem($adapter);
        $filesystem->writeStream('file.txt', 'not a resource');
    }

    public function testUpdateStream()
    {
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $adapter->shouldReceive('has')->andReturn(true);
        $adapter->shouldReceive('updateStream')->andReturn(array('path' => 'file.txt'), false);
        $filesystem = new Filesystem($adapter);
        $this->assertTrue($filesystem->updateStream('file.txt', tmpfile()));
        $filesystem->flushCache();
        $this->assertFalse($filesystem->updateStream('file.txt', tmpfile()));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testUpdateStreamFail()
    {
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $adapter->shouldReceive('has')->andReturn(true);
        $filesystem = new Filesystem($adapter);
        $filesystem->updateStream('file.txt', 'not a resource');
    }

    public function testReadStream()
    {
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $adapter->shouldReceive('has')->andReturn(true);
        $adapter->shouldReceive('readStream')->twice()->andReturn(array('stream' => 'this result'), false);
        $filesystem = new Filesystem($adapter);
        $this->assertEquals('this result', $filesystem->readStream('file.txt'));
        $this->assertFalse($filesystem->readStream('other.txt'));

        // Another time to hit the cache
        $this->assertEquals('this result', $filesystem->readStream('file.txt'));
    }
}
