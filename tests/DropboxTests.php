<?php

use League\Flysystem\Adapter\Dropbox;
use League\Flysystem\Config;

class DropboxTests extends PHPUnit_Framework_TestCase
{
    public function getClientMock()
    {
        $mock = Mockery::mock('Dropbox\Client');
        $mock->shouldReceive('__toString')->andReturn('Dropbox\Client');

        return $mock;
    }

    public function testInstantiable()
    {
        $adapter = new Dropbox($this->getClientMock(), 'prefix');
    }

    public function dropboxProvider()
    {
        $mock = $this->getClientMock();

        return array(
            array(new Dropbox($mock, 'prefix'), $mock),
        );
    }

    /**
     * @dataProvider  dropboxProvider
     */
    public function testWrite($adapter, $mock)
    {
        $mock->shouldReceive('uploadFileFromString')->andReturn(array(
            'is_dir' => false,
            'modified' => '10 September 2000',
        ), false);

        $result = $adapter->write('something', 'contents', new Config);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('file', $result['type']);
        $this->assertFalse($adapter->write('something', 'something', new Config));
    }

    /**
     * @dataProvider  dropboxProvider
     */
    public function testUpdate(Dropbox $adapter, $mock)
    {
        $mock->shouldReceive('uploadFileFromString')->andReturn(array(
            'is_dir' => false,
            'modified' => '10 September 2000',
        ), false);

        $result = $adapter->update('something', 'contents', new Config);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('file', $result['type']);
        $this->assertFalse($adapter->update('something', 'something', new Config));
    }

    /**
     * @dataProvider  dropboxProvider
     */
    public function testWriteStream(Dropbox $adapter, $mock)
    {
        $mock->shouldReceive('uploadFile')->andReturn(array(
            'is_dir' => false,
            'modified' => '10 September 2000',
        ), false);

        $result = $adapter->writeStream('something', 'contents', new Config);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('file', $result['type']);
        $this->assertFalse($adapter->writeStream('something', 'something', new Config));
    }

     /**
     * @dataProvider  dropboxProvider
     */
    public function testUpdateStream(Dropbox $adapter, $mock)
    {
        $mock->shouldReceive('uploadFile')->andReturn(array(
            'is_dir' => false,
            'modified' => '10 September 2000',
        ), false);

        $result = $adapter->updateStream('something', 'contents', new Config);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('file', $result['type']);
        $this->assertFalse($adapter->updateStream('something', 'something', new Config));
    }

    public function metadataProvider()
    {
        return array(
            array('getMetadata'),
            array('getMimetype'),
            array('getTimestamp'),
            array('getSize'),
            array('has'),
        );
    }

    /**
     * @dataProvider  metadataProvider
     */
    public function testMetadataCalls($method)
    {
        $mock = $this->getClientMock();
        $mock->shouldReceive('getMetadata')->twice()->andReturn(array(
            'is_dir' => false,
            'modified' => '10 September 2000',
        ), false);

        $adapter = new Dropbox($mock);
        $this->assertInternalType('array', $adapter->{$method}('one', 'two'));
        $this->assertFalse($adapter->{$method}('one', 'two'));
    }

    /**
     * @dataProvider  dropboxProvider
     */
    public function testRead($adapter, $mock)
    {
        $stream = tmpfile();
        fwrite($stream, 'something');
        $mock->shouldReceive('getFile')->andReturn($stream, false);
        $this->assertInternalType('array', $adapter->read('something'));
        $this->assertFalse($adapter->read('something'));
        fclose($stream);
    }

    /**
     * @dataProvider  dropboxProvider
     */
    public function testReadStream(Dropbox $adapter, $mock)
    {
        $stream = tmpfile();
        fwrite($stream, 'something');
        $mock->shouldReceive('getFile')->andReturn($stream, false);
        $this->assertInternalType('array', $adapter->readStream('something'));
        $this->assertFalse($adapter->readStream('something'));
        fclose($stream);
    }

    /**
     * @dataProvider  dropboxProvider
     */
    public function testDelete(Dropbox $adapter, $mock)
    {
        $mock->shouldReceive('delete')->andReturn(true);
        $this->assertTrue($adapter->delete('something'));
        $this->assertTrue($adapter->deleteDir('something'));
    }

    /**
     * @dataProvider  dropboxProvider
     */
    public function testCreateDir(Dropbox $adapter, $mock)
    {
        $mock->shouldReceive('createFolder')->with('/prefix/fail/please')->andReturn(null);
        $mock->shouldReceive('createFolder')->with('/prefix/pass/please')->andReturn(array(
            'is_dir' => true,
            'path' => 'pass/please',
        ));
        $this->assertFalse($adapter->createDir('fail/please', new Config));
        $expected = ['path' => 'pass/please', 'type' => 'dir'];
        $this->assertEquals($expected, $adapter->createDir('pass/please', new Config));
    }

    /**
     * @dataProvider  dropboxProvider
     */
    public function testListContents(Dropbox $adapter, $mock)
    {
        $mock->shouldReceive('getMetadataWithChildren')->andReturn(
            array('contents' => array(
                array('is_dir' => true, 'path' => 'dirname'),
            )),
            array('contents' => array(
                array('is_dir' => false, 'path' => 'dirname/file'),
            )),
            false
        );

        $result = $adapter->listContents('', true);
        $this->assertCount(2, $result);
        $this->assertEquals(array(), $adapter->listContents('', false));
    }

    /**
     * @dataProvider  dropboxProvider
     */
    public function testRename($adapter, $mock)
    {
        $mock->shouldReceive('move')->andReturn(array('is_dir' => false, 'path' => 'something'));
        $this->assertInternalType('array', $adapter->rename('something', 'something'));
    }

    /**
     * @dataProvider  dropboxProvider
     */
    public function testRenameFail($adapter, $mock)
    {
        $mock->shouldReceive('move')->andReturnUsing(function () {
            throw new \Dropbox\Exception('Message');
        });

        $this->assertFalse($adapter->rename('something', 'something'));
    }

    /**
     * @dataProvider  dropboxProvider
     */
    public function testCopy($adapter, $mock)
    {
        $mock->shouldReceive('copy')->andReturn(array('is_dir' => false, 'path' => 'something'));
        $this->assertInternalType('array', $adapter->copy('something', 'something'));
    }

    /**
     * @dataProvider  dropboxProvider
     */
    public function testCopyFail($adapter, $mock)
    {
        $mock->shouldReceive('copy')->andReturnUsing(function () {
            throw new \Dropbox\Exception('Message');
        });

        $this->assertFalse($adapter->copy('something', 'something'));
    }

    /**
     * @dataProvider  dropboxProvider
     */
    public function testGetClient($adapter, $mock)
    {
        $this->assertEquals($mock, $adapter->getClient());
    }
}
