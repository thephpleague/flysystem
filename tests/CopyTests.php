<?php

use League\Flysystem\Adapter\Copy;

class CopyFile
{
    //
}

class CopyRevision
{
    //
}

class CopyPart
{
    //
}

class CopyTests extends PHPUnit_Framework_TestCase
{
    public function getClientMock()
    {
        $mock = Mockery::mock('Barracuda\Copy\API');
        $mock->shouldReceive('__toString')->andReturn('Barracuda\Copy\API');

        return $mock;
    }

    public function testInstantiable()
    {
        $adapter = new Copy($this->getClientMock(), 'prefix');
    }

    public function copyProvider()
    {
        $mock = $this->getClientMock();

        return array(
            array(new Copy($mock, 'prefix'), $mock),
        );
    }

    /**
     * @dataProvider  copyProvider
     */
    public function testWrite($adapter, $mock)
    {
        $contents = 'contents';

        $mock->shouldReceive('uploadFromString')->andReturn(
            (object)array(
                'type' => 'file',
                'path' => 'something',
                'modified_time' => '10 September 2000',
        ), false);
        $result = $adapter->write('something', $contents);

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('file', $result['type']);
        $this->assertFalse($adapter->write('something', 'something'));
    }

    /**
     * @dataProvider  copyProvider
     */
    public function testUpdate($adapter, $mock)
    {
        $contents = 'contents';

        $mock->shouldReceive('uploadFromString')->andReturn(
            (object)array(
                'type' => 'file',
                'path' => 'something',
                'modified_time' => '10 September 2000',
        ));
        $result = $adapter->update('something', $contents);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('file', $result['type']);
    }

    /**
     * @dataProvider  copyProvider
     */
    public function testWriteStream($adapter, $mock)
    {
        $contents = 'contents';
        $mock->shouldReceive('uploadFromStream')->andReturn(
            (object)array(
                'type' => 'file',
                'path' => 'something',
                'modified_time' => '10 September 2000',
        ), false);

        // generate dummy data file
        $filepath = tempnam(sys_get_temp_dir(), 'copy-unit-test.tmp');
        file_put_contents($filepath, 'copy==');
        $fh = fopen($filepath, 'r');

        $result = $adapter->writeStream('something', $fh);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('file', $result['type']);

        $fh = fopen($filepath, 'r');
        $this->assertFalse($adapter->writeStream('something', $fh));
    }

     /**
     * @dataProvider  copyProvider
     */
    public function testUpdateStream($adapter, $mock)
    {
        $contents = 'contents';

        $mock->shouldReceive('uploadFromStream')->andReturn(
            (object)array(
                'type' => 'file',
                'path' => 'something',
                'modified_time' => '10 September 2000',
        ));

        // generate dummy data file
        $filepath = tempnam(sys_get_temp_dir(), 'copy-unit-test.tmp');
        file_put_contents($filepath, 'copy==');
        $fh = fopen($filepath, 'r');

        $result = $adapter->updateStream('something', $fh);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('file', $result['type']);
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
        $mock->shouldReceive('listPath')->twice()->andReturn(array(
            (object)array(
                'type' => 'file',
                'modified_time' => '10 September 2000',
                'path' => 'something',
                'size' => 15,
                'mime_type' => 'application/octet-stream',
                ),
            ),
            false
        );

        $adapter = new Copy($mock);
        $this->assertInternalType('array', $adapter->{$method}('one', 'two'));
        $this->assertFalse($adapter->{$method}('one', 'two'));
    }

    /**
     * @dataProvider  copyProvider
     */
    public function testRead($adapter, $mock)
    {
        $contents = 'something';

        $mock->shouldReceive('readToString')->andReturn(array('contents' => $contents), false);

        $stream = tmpfile();
        fwrite($stream, 'something');
        $this->assertInternalType('array', $adapter->read('something'));
        $this->assertFalse($adapter->read('something'));
        fclose($stream);
    }

    /**
     * @dataProvider  copyProvider
     */
    public function testReadStream($adapter, $mock)
    {
        $stream = tmpfile();
        fwrite($stream, 'something');
        $mock->shouldReceive('readToStream')->andReturn(array('stream' => $stream), false);
        $this->assertInternalType('array', $adapter->readStream('something'));
        $this->assertFalse($adapter->readStream('something'));
        fclose($stream);
    }

    /**
     * @dataProvider  copyProvider
     */
    public function testDelete($adapter, $mock)
    {
        $mock->shouldReceive('removeFile')->andReturn(true);
        $mock->shouldReceive('removeDir')->andReturn(true);
        $this->assertTrue($adapter->delete('something'));
        $this->assertTrue($adapter->deleteDir('something'));
    }

    /**
     * @dataProvider  copyProvider
     */
    public function testCreateDir($adapter, $mock)
    {
        $mock->shouldReceive('createDir')->andReturn(array(
            (object)array(
                'type' => 'dir',
                'modified_time' => '10 September 2000',
                'path' => 'something',
                ),
            )
        );
        $this->assertInternalType('array', $adapter->createDir('something'));
    }

    /**
     * @dataProvider  copyProvider
     */
    public function testListContents($adapter, $mock)
    {
        $mock->shouldReceive('listPath')->andReturn(array(
            (object)array(
                'type' => 'dir',
                'path' => 'dirname',
                'modified_time' => '10 September 2000',
                ),
            (object)array(
                'type' => 'file',
                'path' => 'dirname/file',
                'modified_time' => '10 September 2000',
                'mime_type' => 'application/octet-stream',
                'size' => 15,
                ),
            ),
            (object)array(),
            false
        );

        $result = $adapter->listContents('', true);
        $this->assertCount(2, $result);
        $this->assertFalse($adapter->listContents('', false));
    }

    /**
     * @dataProvider  copyProvider
     */
    public function testRename($adapter, $mock)
    {
        $mock->shouldReceive('rename')->andReturn((object)array('type' => 'file', 'path' => 'something'), false);
        $this->assertInternalType('array', $adapter->rename('something', 'something'));
        $this->assertFalse($adapter->rename('something', 'something'));
    }

    /**
     * @dataProvider  copyProvider
     */
    public function testCopy($adapter, $mock)
    {
        $mock->shouldReceive('copy')->andReturn((object)array('type' => 'file', 'path' => 'something'));
        $this->assertInternalType('array', $adapter->copy('something', 'something'));
    }
}
