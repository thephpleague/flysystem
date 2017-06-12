<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

class PathPrefixAdapterTests extends \PHPUnit_Framework_TestCase
{
    protected $adapter;
    /**
     * @var AdapterInterface
     */
    protected $prefixAdapter;

    public function setup()
    {
        $this->adapter = $this->getMock(AdapterInterface::class);
        $this->adapter = $this->getMockBuilder(AdapterInterface::class)
            ->disableProxyingToOriginalMethods()
            ->getMock();


        $this->prefixAdapter = new PathPrefixAdapter($this->adapter, 'prefix');
    }



    public function teardown()
    {
    }

    public function testWrite()
    {
        $config = new Config;
        $this->adapter->expects($this->once())
            ->method('write')
            ->with('prefix/test', 'contents', $config);

        $this->prefixAdapter->write('test', 'contents', $config);
    }

    public function testWriteStream()
    {
        $config = new Config;
        $this->adapter->expects($this->once())
            ->method('writeStream')
            ->with('prefix/test', 'contents', $config);

        $this->prefixAdapter->writeStream('test', 'contents', $config);
    }

    public function testUpdate()
    {
        $config = new Config;
        $this->adapter->expects($this->once())
            ->method('update')
            ->with('prefix/test', 'contents', $config);

        $this->prefixAdapter->update('test', 'contents', $config);
    }

    public function testUpdateStream()
    {
        $config = new Config;
        $this->adapter->expects($this->once())
            ->method('updateStream')
            ->with('prefix/test', 'contents', $config);

        $this->prefixAdapter->updateStream('test', 'contents', $config);
    }

    public function testRename()
    {
        $this->adapter->expects($this->once())
            ->method('rename')
            ->with('prefix/test', 'prefix/newtest');

        $this->prefixAdapter->rename('test', 'newtest');
    }

    public function testCopy()
    {
        $this->adapter->expects($this->once())
            ->method('copy')
            ->with('prefix/test', 'prefix/newtest');

        $this->prefixAdapter->copy('test', 'newtest');
    }

    public function testDelete()
    {
        $this->adapter->expects($this->once())
            ->method('delete')
            ->with('prefix/test');

        $this->prefixAdapter->delete('test');
    }

    public function testDeleteDir()
    {
        $this->adapter->expects($this->once())
            ->method('deleteDir')
            ->with('prefix/test');

        $this->prefixAdapter->deleteDir('test');
    }

    public function testCreateDir()
    {
        $config = new Config;
        $this->adapter->expects($this->once())
            ->method('createDir')
            ->with('prefix/test', $config);

        $this->prefixAdapter->createDir('test', $config);
    }

    public function testSetVisibility()
    {
        $this->adapter->expects($this->once())
            ->method('setVisibility')
            ->with('prefix/test', 'hidden');

        $this->prefixAdapter->setVisibility('test', 'hidden');
    }

    public function testHas()
    {
        $this->adapter->expects($this->once())
            ->method('has')
            ->with('prefix/test');

        $this->prefixAdapter->has('test');
    }

    public function testRead()
    {
        $this->adapter->expects($this->once())
            ->method('read')
            ->with('prefix/test');

        $this->prefixAdapter->read('test');
    }

    public function testReadStream()
    {
        $this->adapter->expects($this->once())
            ->method('readStream')
            ->with('prefix/test');

        $this->prefixAdapter->readStream('test');
    }

    public function testListContents()
    {
        $this->adapter->expects($this->once())
            ->method('listContents')
            ->with('prefix/test');

        $this->prefixAdapter->listContents('test');
    }

    public function testGetMetadata()
    {
        $this->adapter->expects($this->once())
            ->method('getMetadata')
            ->with('prefix/test');

        $this->prefixAdapter->getMetadata('test');
    }

    public function testGetMimetype()
    {
        $this->adapter->expects($this->once())
            ->method('getMimetype')
            ->with('prefix/test');

        $this->prefixAdapter->getMimetype('test');
    }

    public function testGetTimestamp()
    {
        $this->adapter->expects($this->once())
            ->method('getTimestamp')
            ->with('prefix/test');

        $this->prefixAdapter->getTimestamp('test');
    }

    public function testGetVisibility()
    {
        $this->adapter->expects($this->once())
            ->method('getVisibility')
            ->with('prefix/test');

        $this->prefixAdapter->getVisibility('test');
    }

}
