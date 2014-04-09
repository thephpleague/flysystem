<?php

use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Filesystem;

class NullAdapterTest extends PHPUnit_Framework_TestCase
{
    protected function getFilesystem()
    {
        return new Filesystem(new NullAdapter);
    }

    public function testWrite()
    {
        $fs = $this->getFilesystem();
        $result = $fs->write('path', 'contents');
        $this->assertTrue($result);
        $this->assertTrue($fs->has('path'));
    }

    /**
     * @expectedException  \League\Flysystem\FileNotFoundException
     */
    public function testRead()
    {
        $fs = $this->getFilesystem();
        $fs->read('something');
    }

    public function testHas()
    {
        $fs = $this->getFilesystem();
        $this->assertFalse($fs->has('something'));
    }

    public function testDelete()
    {
        $fs = $this->getFilesystem();
        $this->assertFalse($fs->has('something'));
    }

    public function expectedFailsProvider()
    {
        return array(
            array('read'),
            array('update'),
            array('read'),
            array('rename'),
            array('delete'),
            array('listContents'),
            array('getMetadata'),
            array('getSize'),
            array('getMimetype'),
            array('getTimestamp'),
            array('getVisibility'),
            array('deleteDir'),
        );
    }

    /**
     * @dataProvider expectedFailsProvider
     */
    public function testExpectedFails($method)
    {
        $adapter = new NullAdapter;
        $this->assertFalse($adapter->{$method}('one', 'two', 'three'));
    }

    public function expectedArrayResultProvider()
    {
        return array(
            array('write'),
            array('createDir'),
            array('setVisibility'),
        );
    }

    /**
     * @dataProvider expectedArrayResultProvider
     */
    public function testArrayResult($method)
    {
        $adapter = new NullAdapter;
        $this->assertInternalType('array', $adapter->{$method}('one', tmpfile(), array('visibility' => 'public')));
    }
}
