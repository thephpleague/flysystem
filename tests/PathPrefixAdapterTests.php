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
        $this->adapter = $this->getMock('League\Flysystem\AdapterInterface');
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

}
