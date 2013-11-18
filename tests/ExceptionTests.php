<?php

class ExceptionTests extends PHPUnit_Framework_TestCase
{
    public function exceptionProvider()
    {
        return array(
            array('read', true),
            array('update', true),
            array('write', false),
            array('delete', true),
            array('rename', true),
            array('put', false),
            array('put', true),
            array('getMetadata', true),
            array('getVisibility', true),
            array('setVisibility', true),
            array('getMimetype', true),
            array('getTimestamp', true),
            array('getSize', true),
            array('listContents', true),
            array('createDir', true),
            array('deleteDir', true),
        );
    }

    /**
     * @dataProvider exceptionProvider
     * @expectedException Flysystem\AdapterException
     */
    public function testExceptions($method, $shouldExist)
    {
        $mock = Mockery::mock('Flysystem\AdapterInterface');
        $mock->shouldReceive('has')->andReturn($shouldExist, false);
        $mock->shouldReceive($method)->andThrow('Exception', 'with a message');
        $cache = Mockery::mock('Flysystem\CacheInterface');
        $cache->shouldReceive('has')->andReturn($shouldExist, false);
        $filesystem = new Flysystem\Filesystem($mock);
        $filesystem->{$method}(1,2,3);
    }

    /**
     * @expectedException Flysystem\AdapterException
     */
    public function testHasExceptions($method = 'has')
    {
        $mock = Mockery::mock('Flysystem\AdapterInterface');
        $mock->shouldReceive($method)->andThrow('Exception', 'with a message');
        $cache = Mockery::mock('Flysystem\CacheInterface');
        $cache->shouldReceive('has')->andReturn(false);
        $filesystem = new Flysystem\Filesystem($mock);
        $filesystem->{$method}(1,2,3);
    }

    // /**
    //  * @expectedException Flysystem\AdapterException
    //  */
    // public function testRenameExceptions($method = 'rename')
    // {
    //     $mock = Mockery::mock('Flysystem\AdapterInterface');
    //     $mock->shouldReceive('rename')->andThrow('Exception', 'with a message');
    //     $cache = Mockery::mock('Flysystem\CacheInterface');
    //     $cache->shouldReceive('has')->andReturn(false);
    //     $filesystem = new Flysystem\Filesystem($mock);
    //     $filesystem->{$method}('exists','notexists');
    // }
}