<?php

use League\Flysystem\Filesystem;

class FlysystemStreamTests extends PHPUnit_Framework_TestCase
{
    public function testWriteStream()
    {
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $adapter->shouldReceive('has')->andReturn(false);
        $adapter->shouldReceive('writeStream')->andReturn(['path' => 'file.txt'], false);
        $filesystem = new Filesystem($adapter);
        $this->assertTrue($filesystem->writeStream('file.txt', tmpfile()));
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
        $adapter->shouldReceive('updateStream')->andReturn(['path' => 'file.txt'], false);
        $filesystem = new Filesystem($adapter);
        $this->assertTrue($filesystem->updateStream('file.txt', tmpfile()));
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
        $stream = tmpfile();
        $adapter->shouldReceive('readStream')->times(3)->andReturn(['stream' => $stream], false, false);
        $filesystem = new Filesystem($adapter);
        $this->assertInternalType('resource', $filesystem->readStream('file.txt'));
        $this->assertFalse($filesystem->readStream('other.txt'));
        fclose($stream);
        $this->assertFalse($filesystem->readStream('other.txt'));
    }
}
