<?php

use League\Flysystem\AdapterInterface;
use League\Flysystem\AppendableAdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class FlysystemStreamTests extends TestCase
{
    use \PHPUnitHacks;

    public function testWriteStream()
    {
        $stream = tmpfile();
        $adapter = $this->prophesize(AdapterInterface::class);
        $adapter->has('file.txt')->willReturn(false)->shouldBeCalled();
        $adapter->writeStream('file.txt', $stream, Argument::type(Config::class))
            ->willReturn(['path' => 'file.txt'], false)
            ->shouldBeCalled();
        $filesystem = new Filesystem($adapter->reveal());
        $this->assertTrue($filesystem->writeStream('file.txt', $stream));
        $this->assertFalse($filesystem->writeStream('file.txt', $stream));
    }

    public function testAppendStream()
    {
        $stream = tmpfile();
        $adapter = $this->prophesize(AdapterInterface::class)->willImplement(AppendableAdapterInterface::class);
        $adapter->appendStream('file.txt', $stream, Argument::type(Config::class))->shouldBeCalled()->willReturn(['path' => 'file.txt']);
        $filesystem = new Filesystem($adapter->reveal());
        $this->assertTrue($filesystem->appendStream('file.txt', $stream));
        $this->assertTrue($filesystem->appendStream('file.txt', $stream));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWriteStreamFail()
    {
        $filesystem = new Filesystem($this->createMock(AdapterInterface::class));
        $filesystem->writeStream('file.txt', 'not a resource');
    }

    public function testUpdateStream()
    {
        $stream = tmpfile();
        $adapter = $this->prophesize(AdapterInterface::class);
        $adapter->has('file.txt')->willReturn(true)->shouldBeCalled();

        $adapter->updateStream('file.txt', $stream, Argument::type(Config::class))
            ->willReturn(['path' => 'file.txt'], false)
            ->shouldBeCalled();

        $filesystem = new Filesystem($adapter->reveal());

        $this->assertTrue($filesystem->updateStream('file.txt', $stream));
        $this->assertFalse($filesystem->updateStream('file.txt', $stream));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testUpdateStreamFail()
    {
        $filesystem = new Filesystem($this->createMock(AdapterInterface::class));
        $filesystem->updateStream('file.txt', 'not a resource');
    }

    public function testReadStream()
    {
        $adapter = $this->prophesize(AdapterInterface::class);
        $adapter->has(Argument::type('string'))->willReturn(true)->shouldBeCalled();
        $stream = tmpfile();
        $adapter->readStream('file.txt')->willReturn(['stream' => $stream])->shouldBeCalled();
        $adapter->readStream('other.txt')->willReturn(false)->shouldBeCalled();
        $filesystem = new Filesystem($adapter->reveal());
        $this->assertInternalType('resource', $filesystem->readStream('file.txt'));
        $this->assertFalse($filesystem->readStream('other.txt'));
        fclose($stream);
        $this->assertFalse($filesystem->readStream('other.txt'));
    }
}
