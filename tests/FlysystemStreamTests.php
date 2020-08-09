<?php

use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class FlysystemStreamTests extends TestCase
{

    public function testWriteStream()
    {
        $stream = tmpfile();
        $adapter = $this->prophesize('League\Flysystem\AdapterInterface');
        $adapter->has('file.txt')->willReturn(false)->shouldBeCalled();
        $adapter->writeStream('file.txt', $stream, Argument::type('League\Flysystem\Config'))
            ->willReturn(['path' => 'file.txt'], false)
            ->shouldBeCalled();
        $filesystem = new Filesystem($adapter->reveal());
        $this->assertTrue($filesystem->writeStream('file.txt', $stream));
        $this->assertFalse($filesystem->writeStream('file.txt', $stream));
    }

    public function testWriteStreamFail()
    {
        $this->expectException(InvalidArgumentException::class);
        $filesystem = new Filesystem(new Local(__DIR__));
        $filesystem->writeStream('file.txt', 'not a resource');
    }

    public function testUpdateStream()
    {
        $stream = tmpfile();
        $adapter = $this->prophesize('League\Flysystem\AdapterInterface');
        $adapter->has('file.txt')->willReturn(true)->shouldBeCalled();

        $adapter->updateStream('file.txt', $stream, Argument::type('League\Flysystem\Config'))
            ->willReturn(['path' => 'file.txt'], false)
            ->shouldBeCalled();

        $filesystem = new Filesystem($adapter->reveal());

        $this->assertTrue($filesystem->updateStream('file.txt', $stream));
        $this->assertFalse($filesystem->updateStream('file.txt', $stream));
    }

    public function testUpdateStreamFail()
    {
        $this->expectException(InvalidArgumentException::class);
        $filesystem = new Filesystem(new Local(__DIR__));
        $filesystem->updateStream('file.txt', 'not a resource');
    }

    public function testReadStream()
    {
        $adapter = $this->prophesize('League\Flysystem\AdapterInterface');
        $adapter->has(Argument::type('string'))->willReturn(true)->shouldBeCalled();
        $stream = tmpfile();
        $adapter->readStream('file.txt')->willReturn(['stream' => $stream])->shouldBeCalled();
        $adapter->readStream('other.txt')->willReturn(false)->shouldBeCalled();
        $filesystem = new Filesystem($adapter->reveal());
        $this->assertIsResource($filesystem->readStream('file.txt'));
        $this->assertFalse($filesystem->readStream('other.txt'));
        fclose($stream);
        $this->assertFalse($filesystem->readStream('other.txt'));
    }
}
