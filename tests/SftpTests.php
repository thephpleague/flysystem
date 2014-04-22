<?php

use League\Flysystem\Adapter\Sftp;
use League\Flysystem\Filesystem;

class SftpTests extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('SFTP is not supported on HHVM');
            return;
        }

        class_exists('Net_SFTP', true);

        if ( ! defined('NET_SFTP_TYPE_DIRECTORY')) {
            define('NET_SFTP_TYPE_DIRECTORY', 2);
        }
    }

    public function adapterProvider()
    {
        $adapter = new Sftp(array());
        $mock = Mockery::mock('Net_SFTP');
        $mock->shouldReceive('__toString')->andReturn('Net_SFTP');
        $mock->shouldReceive('disconnect');
        $adapter->setNetSftpConnection($mock);
        $filesystem = new Filesystem($adapter);

        return array(
            array($filesystem, $adapter, $mock),
        );
    }

    /**
     * @dataProvider adapterProvider
     */
    public function testHas($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('stat')->andReturn(array(
            'type' => NET_SFTP_TYPE_DIRECTORY,
            'mtime' => time(),
            'size' => 20,
            'permissions' => 0777,
        ));

        $this->assertTrue($filesystem->has('something'));
    }

    /**
     * @dataProvider adapterProvider
     */
    public function testHasFail($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('stat')->andReturn(false);

        $this->assertFalse($filesystem->has('something'));
    }

    /**
     * @dataProvider adapterProvider
     */
    public function testWrite($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('put')->andReturn(true, false);
        $mock->shouldReceive('stat')->andReturn(false);
        $this->assertTrue($filesystem->write('something', 'something'));
        $this->assertFalse($filesystem->write('something_else.txt', 'else'));
    }

    /**
     * @dataProvider adapterProvider
     */
    public function testDelete($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('delete')->andReturn(true, false);
        $mock->shouldReceive('stat')->andReturn(array(
            'type' => 1,
            'mtime' => time(),
            'size' => 20,
            'permissions' => 0777,
        ));
        $this->assertTrue($filesystem->delete('something'));
        $this->assertFalse($filesystem->delete('something_else.txt'));
    }

    /**
     * @dataProvider adapterProvider
     */
    public function testUpdate($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('put')->andReturn(true, false);
        $mock->shouldReceive('stat')->andReturn(array(
            'type' => NET_SFTP_TYPE_DIRECTORY,
            'mtime' => time(),
            'size' => 20,
            'permissions' => 0777,
        ));
        $this->assertTrue($filesystem->update('something', 'something'));
        $this->assertFalse($filesystem->update('something_else.txt', 'else'));
    }

    /**
     * @dataProvider adapterProvider
     */
    public function testSetVisibility($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('chmod')->twice()->andReturn(true, false);
        $this->assertTrue($filesystem->setVisibility('something', 'public'));
        $this->assertFalse($filesystem->setVisibility('something', 'public'));
    }

    /**
     * @dataProvider adapterProvider
     * @expectedException InvalidArgumentException
     */
    public function testSetVisibilityInvalid($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('stat')->once()->andReturn(true);
        $filesystem->setVisibility('something', 'invalid');
    }

    /**
     * @dataProvider adapterProvider
     */
    public function testRename($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('stat')->andReturn(array(
            'type' => NET_SFTP_TYPE_DIRECTORY,
            'mtime' => time(),
            'size' => 20,
            'permissions' => 0777,
        ), false);
        $mock->shouldReceive('rename')->andReturn(true);
        $result = $filesystem->rename('old', 'new');
        $this->assertTrue($result);
    }

    /**
     * @dataProvider adapterProvider
     */
    public function testDeleteDir($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('delete')->with('some/dirname', true)->andReturn(true);
        $result = $filesystem->deleteDir('some/dirname');
        $this->assertTrue($result);
    }

    /**
     * @dataProvider adapterProvider
     */
    public function testListContents($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('rawlist')->andReturn(false, array(
            '.' => array(),
            'dirname' => array(
                'type' => NET_SFTP_TYPE_DIRECTORY,
                'mtime' => time(),
                'size' => 20,
                'permissions' => 0777,
            )
        ), array(
            '..' => array(),
            'dirname' => array(
                'type' => 1,
                'mtime' => time(),
                'size' => 20,
                'permissions' => 0777,
            )
        ));
        $listing = $filesystem->listContents('', true);
        $this->assertInternalType('array', $listing);
        $filesystem->getCache()->flush();
        $listing = $filesystem->listContents('', true);
        $this->assertInternalType('array', $listing);
        $this->assertCount(2, $listing);
    }


    public function methodProvider()
    {
        $resources = $this->adapterProvider();
        list ($filesystem, $adapter, $mock) = reset($resources);

        return array(
            array($filesystem, $adapter, $mock, 'getMetadata', 'array'),
            array($filesystem, $adapter, $mock, 'getTimestamp', 'integer'),
            array($filesystem, $adapter, $mock, 'getVisibility', 'string'),
            array($filesystem, $adapter, $mock, 'getSize', 'integer'),
        );
    }

    /**
     * @dataProvider  methodProvider
     */
    public function testMetaMethods($filesystem, $adapter, $mock, $method, $type)
    {
        $filesystem->flushCache();
        $mock->shouldReceive('stat')->andReturn(array(
            'type' => NET_SFTP_TYPE_DIRECTORY,
            'mtime' => time(),
            'size' => 20,
            'permissions' => 0777,
        ));
        $result = $filesystem->{$method}(uniqid().'object.ext');
        $this->assertInternalType($type, $result);
    }

    /**
     * @dataProvider  adapterProvider
     */
    public function testGetVisibility($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('stat')->andReturn(array(
            'type' => NET_SFTP_TYPE_DIRECTORY,
            'mtime' => time(),
            'size' => 20,
            'permissions' => 0777,
        ));
        $result = $adapter->getVisibility(uniqid().'object.ext');
        $this->assertInternalType('array', $result);
        $result = $result['visibility'];
        $this->assertInternalType('string', $result);
        $this->assertEquals('public', $result);
    }

    /**
     * @dataProvider  adapterProvider
     */
    public function testGetTimestamp($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('stat')->andReturn(array(
            'type' => NET_SFTP_TYPE_DIRECTORY,
            'mtime' => $time = time(),
            'size' => 20,
            'permissions' => 0777,
        ));
        $result = $adapter->getTimestamp('object.ext');
        $this->assertInternalType('array', $result);
        $result = $result['timestamp'];
        $this->assertInternalType('integer', $result);
        $this->assertEquals($time, $result);
    }

    /**
     * @dataProvider  adapterProvider
     */
    public function testCreateDir($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('mkdir')->andReturn(true, false);
        $this->assertTrue($filesystem->createDir('dirname'));
        $this->assertFalse($filesystem->createDir('dirname_fails'));
    }

    /**
     * @dataProvider  adapterProvider
     */
    public function testRead($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('stat')->andReturn(array(
            'type' => 1,
            'mtime' => time(),
            'size' => 20,
            'permissions' => 0777,
        ));
        $mock->shouldReceive('get')->andReturn('file contents', false);
        $result = $filesystem->read('some.file');
        $this->assertInternalType('string', $result);
        $this->assertEquals('file contents', $result);
        $this->assertFalse($filesystem->read('other.file'));
    }

    /**
     * @dataProvider  adapterProvider
     */
    public function testGetMimetype($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('stat')->andReturn(array(
            'type' => 1,
            'mtime' => time(),
            'size' => 20,
            'permissions' => 0777,
        ));
        $mock->shouldReceive('get')->andReturn('file contents', false);
        $result = $filesystem->getMimetype('some.file');
        $this->assertInternalType('string', $result);
        $this->assertEquals('text/plain', $result);
        $filesystem->flushCache();
        $this->assertFalse($filesystem->getMimetype('some.file'));
    }
}
