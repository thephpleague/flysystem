<?php

use League\Flysystem\Adapter\WebDav as Adapter;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;

class WebDavTests extends PHPUnit_Framework_TestCase
{
    protected function getClient()
    {
        return Mockery::mock('Sabre\DAV\Client');
    }

    public function testHas()
    {
        $mock = $this->getClient();
        $mock->shouldReceive('propFind')->once()->andReturn([
            '{DAV:}getcontentlength' => 20,
        ]);
        $adapter = new Filesystem(new Adapter($mock));
        $this->assertTrue($adapter->has('something'));
    }

    public function testHasFail()
    {
        $mock = $this->getClient();
        $mock->shouldReceive('propFind')->once()->andThrow('Sabre\DAV\Exception\FileNotFound');
        $adapter = new Adapter($mock);
        $this->assertFalse($adapter->has('something'));
    }

    public function testWrite()
    {
        $mock = $this->getClient();
        $mock->shouldReceive('request')->once();
        $adapter = new Adapter($mock);
        $this->assertInternalType('array', $adapter->write('something', 'something', new Config()));
    }

    public function testUpdate()
    {
        $mock = $this->getClient();
        $mock->shouldReceive('request')->once();
        $adapter = new Adapter($mock);
        $this->assertInternalType('array', $adapter->update('something', 'something', new Config()));
    }

    /**
     * @expectedException LogicException
     */
    public function testWriteVisibility()
    {
        $mock = $this->getClient();
        $mock->shouldReceive('request')->once();
        $adapter = new Adapter($mock);
        $this->assertInternalType('array', $adapter->write('something', 'something', new Config([
            'visibility' => 'private',
        ])));
    }

    public function testReadStream()
    {
        $mock = $this->getClient();
        $mock->shouldReceive('request')->andReturn([
            'statusCode' => 200,
            'body' => 'contents',
            'headers' => [
                'last-modified' => date('Y-m-d H:i:s'),
            ],
        ]);
        $adapter = new Adapter($mock, 'bucketname', 'prefix');
        $result = $adapter->readStream('file.txt');
        $this->assertInternalType('resource', $result['stream']);
    }

    public function testRename()
    {
        $mock = $this->getClient();
        $mock->shouldReceive('request')->once()->andReturn([
            'statusCode' => 200,
        ]);
        $adapter = new Adapter($mock, 'bucketname');
        $result = $adapter->rename('old', 'new');
        $this->assertTrue($result);
    }

    public function testRenameFail()
    {
        $mock = $this->getClient();
        $mock->shouldReceive('request')->once()->andReturn([
            'statusCode' => 404,
        ]);
        $adapter = new Adapter($mock, 'bucketname');
        $result = $adapter->rename('old', 'new');
        $this->assertFalse($result);
    }

    public function testRenameFailException()
    {
        $mock = $this->getClient();
        $mock->shouldReceive('request')->once()->andThrow('Sabre\DAV\Exception\FileNotFound');
        $adapter = new Adapter($mock, 'bucketname');
        $result = $adapter->rename('old', 'new');
        $this->assertFalse($result);
    }

    public function testDeleteDir()
    {
        $mock = $this->getClient();
        $mock->shouldReceive('request')->with('DELETE', 'some/dirname')->once()->andReturn(true);
        $adapter = new Adapter($mock);
        $result = $adapter->deleteDir('some/dirname');
        $this->assertTrue($result);
    }

    public function testDeleteDirFail()
    {
        $mock = $this->getClient();
        $mock->shouldReceive('request')->with('DELETE', 'some/dirname')->once()->andThrow('Sabre\DAV\Exception\FileNotFound');
        $adapter = new Adapter($mock);
        $result = $adapter->deleteDir('some/dirname');
        $this->assertFalse($result);
    }

    public function testListContents()
    {
        $mock = $this->getClient();
        $first = [
            [],
            'filename' => [
                '{DAV:}getcontentlength' => 20,
            ],
            'dirname' => [
                // '{DAV:}getcontentlength' => 20,
            ],
        ];

        $second = [
            [],
            'deeper_filename.ext' => [
                '{DAV:}getcontentlength' => 20,
            ],
        ];
        $mock->shouldReceive('propFind')->twice()->andReturn($first, $second);
        $adapter = new Adapter($mock, 'bucketname');
        $listing = $adapter->listContents('', true);
        $this->assertInternalType('array', $listing);
    }

    public function methodProvider()
    {
        return [
            ['getMetadata'],
            ['getTimestamp'],
            ['getMimetype'],
            ['getSize'],
        ];
    }

    /**
     * @dataProvider  methodProvider
     */
    public function testMetaMethods($method)
    {
        $mock = $this->getClient();
        $mock->shouldReceive('propFind')->once()->andReturn([
            '{DAV:}displayname' => 'object.ext',
            '{DAV:}getcontentlength' => 30,
            '{DAV:}getcontenttype' => 'plain/text',
            '{DAV:}getlastmodified' => date('Y-m-d H:i:s'),
        ]);
        $adapter = new Adapter($mock);
        $result = $adapter->{$method}('object.ext');
        $this->assertInternalType('array', $result);
    }

    public function testCreateDir()
    {
        $mock = $this->getClient();
        $mock->shouldReceive('request')->with('MKCOL', 'dirname')->once()->andReturn([
            'statusCode' => 201,
        ]);
        $adapter = new Adapter($mock);
        $result = $adapter->createDir('dirname', new Config());
        $this->assertInternalType('array', $result);
    }

    public function testCreateDirFail()
    {
        $mock = $this->getClient();
        $mock->shouldReceive('request')->with('MKCOL', 'dirname')->once()->andReturn([
            'statusCode' => 500,
        ]);
        $adapter = new Adapter($mock);
        $result = $adapter->createDir('dirname', new Config());
        $this->assertFalse($result);
    }

    public function testRead()
    {
        $mock = $this->getClient();
        $mock->shouldReceive('request')->andReturn([
            'statusCode' => 200,
            'body' => 'contents',
            'headers' => [
                'last-modified' => date('Y-m-d H:i:s'),
            ],
        ]);
        $adapter = new Adapter($mock, 'bucketname', 'prefix');
        $result = $adapter->read('file.txt');
        $this->assertInternalType('array', $result);
    }

    public function testReadFail()
    {
        $mock = $this->getClient();
        $mock->shouldReceive('request')->andReturn([
            'statusCode' => 404,
            'body' => 'contents',
            'headers' => [
                'last-modified' => date('Y-m-d H:i:s'),
            ],
        ]);
        $adapter = new Adapter($mock, 'bucketname', 'prefix');
        $result = $adapter->read('file.txt');
        $this->assertFalse($result);
    }

    public function testReadStreamFail()
    {
        $mock = $this->getClient();
        $mock->shouldReceive('request')->andReturn([
            'statusCode' => 404,
            'body' => 'contents',
            'headers' => [
                'last-modified' => date('Y-m-d H:i:s'),
            ],
        ]);
        $adapter = new Adapter($mock, 'bucketname', 'prefix');
        $result = $adapter->readStream('file.txt');
        $this->assertFalse($result);
    }

    public function testReadException()
    {
        $mock = $this->getClient();
        $mock->shouldReceive('request')->andThrow('Sabre\DAV\Exception\FileNotFound');
        $adapter = new Adapter($mock, 'bucketname', 'prefix');
        $result = $adapter->read('file.txt');
        $this->assertFalse($result);
    }
}
