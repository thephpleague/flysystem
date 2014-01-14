<?php

use League\Flysystem\Adapter\AwsS3 as Adapter;
use Aws\S3\Enum\Group;
use Aws\S3\Enum\Permission;

class StreamMock
{
    public function stream_open()
    {
        return true;
    }
}

class AwsS3Tests extends PHPUnit_Framework_TestCase
{
    protected function getS3Client()
    {
        return Mockery::mock('Aws\S3\S3Client');
    }

    public function testHas()
    {
        $mock = $this->getS3Client();
        $mock->shouldReceive('doesObjectExist')->once()->andReturn(true);
        $adapter = new Adapter($mock, 'bucketname');
        $this->assertTrue($adapter->has('something'));
    }

    public function testWrite()
    {
        $mock = $this->getS3Client();
        $mock->shouldReceive('putObject')->times(4);
        $adapter = new Adapter($mock, 'bucketname', 'prefix');
        $adapter->update('something', 'something');
        $adapter->write('something', 'something', 'private');
        $adapter->writeStream('something', 'something', array(
            'visibility' => 'private',
            'mimetype' => 'text/plain',
        ));
        $adapter->updateStream('something', 'something');
    }

    public function testReadStream()
    {
        $mock = $this->getS3Client();
        $mock->shouldReceive('registerStreamWrapper')->once()->andReturnUsing(function () {
            stream_wrapper_register('s3', 'StreamMock');
        });
        $adapter = new Adapter($mock, 'bucketname', 'prefix');
        $result = $adapter->readStream('file.txt');
        $this->assertInternalType('resource', $result['stream']);
    }

    public function testRename()
    {
        $mock = $this->getS3Client();
        $mock->shouldReceive('copyObject')->once()->andReturn(Mockery::self());
        $mock->shouldReceive('getAll')->once()->andReturn(array('Key' => 'something', 'LastModified' => '2011-01-01'));
        $mock->shouldReceive('deleteObject')->once()->andReturn(true);
        $adapter = new Adapter($mock, 'bucketname');
        $result = $adapter->rename('old', 'new');
        $this->assertArrayHasKey('path', $result);
        $this->assertContains('new', $result);
        $this->assertInternalType('int', $result['timestamp']);
    }

    public function testDeleteDir()
    {
        $mock = $this->getS3Client();
        $mock->shouldReceive('deleteMatchingObjects')->with('bucketname', 'some/dirname')->once()->andReturn(true);
        $adapter = new Adapter($mock, 'bucketname');
        $result = $adapter->deleteDir('some/dirname');
        $this->assertTrue($result);
    }

    public function testListContents()
    {
        $mock = $this->getS3Client();
        $mock->shouldReceive('listObjects')->once()->andReturn(Mockery::self());
        $result = array('Contents' => array(array('Key' => 'path', 'ContentLength' => 20, 'ContentType' => 'text/plain')));
        $mock->shouldReceive('getAll')->with(array('Contents'))->andReturn($result);
        $adapter = new Adapter($mock, 'bucketname');
        $listing = $adapter->listContents();
        $this->assertCount(1, $listing);
        $item = reset($listing);
        $this->assertArrayHasKey('path', $item);
        $this->assertArrayHasKey('type', $item);
        $this->assertArrayHasKey('mimetype', $item);
    }

    public function testSetVisibility()
    {
        $mock = $this->getS3Client();
        $mock->shouldReceive('putObjectAcl')->once();
        $adapter = new Adapter($mock, 'bucketname');
        $result = $adapter->setVisibility('object.ext', 'public');
        $this->assertEquals(array('visibility' => 'public'), $result);
    }

    public function visibilityProvider()
    {
        return array(
            array(Permission::READ, Group::ALL_USERS, 'public'),
            array('other', Group::ALL_USERS, 'private'),
            array('other', 'invalid', 'private'),
        );
    }

    /**
     * @dataProvider  visibilityProvider
     */
    public function testGetVisibility($permission, $uri, $expected)
    {
        $mock = $this->getS3Client();
        $mock->shouldReceive('getObjectAcl')->once()->andReturn(Mockery::self());
        $grant = array('Permission' => $permission, 'Grantee' => array('URI' => $uri));
        $grants = array('Grants' => array($grant));
        $mock->shouldReceive('getAll')->once()->andReturn($grants);
        $expected = array('visibility' => $expected);
        $adapter = new Adapter($mock, 'bucketname');
        $result = $adapter->getVisibility('object.ext');
        $this->assertEquals($expected, $result);
    }

    public function methodProvider()
    {
        return array(
            array('getMetadata'),
            array('getTimestamp'),
            array('getMimetype'),
            array('getSize'),
        );
    }

    /**
     * @dataProvider  methodProvider
     */
    public function testMetaMethods($method)
    {
        $mock = $this->getS3Client();
        $mock->shouldReceive('headObject')->once()->andReturn(Mockery::self());
        $mock->shouldReceive('getAll')->once()->andReturn(array('ContentLength' => 20, 'ContentType' => 'text/plain'));
        $adapter = new Adapter($mock, 'bucketname');
        $result = $adapter->{$method}('object.ext');
        $this->assertInternalType('array', $result);
    }

    public function testCreateDir()
    {
        $adapter = new Adapter($this->getS3Client(), 'bucketname');
        $result = $adapter->createDir('something');
        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('something', $result['path']);
        $this->assertEquals('dir', $result['type']);
    }

    public function testRead()
    {
        $mock = $this->getS3Client();
        $mock->shouldReceive('getObject')->once()->andReturn(Mockery::self());
        $mock->shouldReceive('getAll')->once()->andReturn(array('ContentLength' => 10, 'ContentType' => 'text/plain', 'Body' => '1234567890', 'Key' => 'file.ext'));
        $adapter = new Adapter($mock, 'bucketname');
        $result = $adapter->read('file.ext');
        $this->assertEquals('1234567890', $result['contents']);
    }
}
