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
        $credentials = $this->getMock('Aws\Common\Credentials\CredentialsInterface');
        $signature = $this->getMock('Aws\Common\Signature\SignatureInterface');
        $client = $this->getMock('Guzzle\Common\Collection');

        return Mockery::mock('Aws\S3\S3Client[putObject,copyObject,getAll,deleteObject,deleteMatchingObjects,getIterator,putObjectAcl,getAll,getObjectAcl,doesObjectExist,GetObject,registerStreamWrapper]', array(
            $credentials,
            $signature,
            $client,
        ));
    }

    protected function getUploadBuilder()
    {
        return Mockery::mock(
            'Aws\S3\Model\MultipartUpload\UploadBuilder[build,setSource,setBucket,setKey,setMinPartSize,setOption,setConcurrency,setSource]'
        );
    }

    protected function getAbstractTransfer()
    {
        return Mockery::mock('Aws\S3\Model\MultipartUpload\AbstractTransfer');
    }

    public function testHas()
    {
        $mock = $this->getS3Client();
        $mock->shouldReceive('doesObjectExist')->once()->andReturn(true);
        $adapter = new Adapter($mock, 'bucketname');
        $this->assertTrue($adapter->has('something'));
    }

    public function testGetClient()
    {
        $mock = $this->getS3Client();
        $adapter = new Adapter($mock, 'bucket');
        $this->assertInstanceOf('Aws\S3\S3Client', $adapter->getClient());
    }

    public function testWrite()
    {
        $mock = $this->getS3Client();
        $mock->shouldReceive('putObject')->times(2);
        $adapter = new Adapter($mock, 'bucketname', 'prefix');
        $adapter->update('something', 'something');
        $adapter->write('something', 'something', 'private');
    }

    public function testWriteAboveLimit()
    {
        $mockS3Client = $this->getS3Client();
        $mockTransfer = $this->getAbstractTransfer();
        $mockTransfer->shouldReceive('upload')->once();
        $mockUploadBuilder = $this->getUploadBuilder();
        $mockUploadBuilder->shouldReceive('setBucket')->once()->andReturn($mockUploadBuilder);
        $mockUploadBuilder->shouldReceive('setKey')->once()->andReturn($mockUploadBuilder);
        $mockUploadBuilder->shouldReceive('setMinPartSize')->once()->andReturn($mockUploadBuilder);
        $mockUploadBuilder->shouldReceive('setOption')->andReturn($mockUploadBuilder);
        $mockUploadBuilder->shouldReceive('setConcurrency')->once()->andReturn($mockUploadBuilder);
        $mockUploadBuilder->shouldReceive('setSource')->once()->andReturn($mockUploadBuilder);
        $mockUploadBuilder->shouldReceive('build')->once()->andReturn($mockTransfer);

        $adapter = new Adapter($mockS3Client, 'bucketname', 'prefix', array(
            'Multipart' => -10
        ), $mockUploadBuilder);

        $adapter->write(
            'something',
            'some content',
            array(
                'visibility' => 'private',
                'mimetype'   => 'text/plain',
                'Expires'    => 'it does',
                'Metadata' => array(),
            )
        );
    }

    public function testWriteStreamAboveLimit()
    {
        $mockS3Client = $this->getS3Client();
        $mockTransfer = $this->getAbstractTransfer();
        $mockTransfer->shouldReceive('upload')->times(2);
        $mockUploadBuilder = $this->getUploadBuilder();
        $mockUploadBuilder->shouldReceive('setBucket')->times(2)->andReturn($mockUploadBuilder);
        $mockUploadBuilder->shouldReceive('setKey')->times(2)->andReturn($mockUploadBuilder);
        $mockUploadBuilder->shouldReceive('setMinPartSize')->times(2)->andReturn($mockUploadBuilder);
        $mockUploadBuilder->shouldReceive('setOption')->andReturn($mockUploadBuilder);
        $mockUploadBuilder->shouldReceive('setConcurrency')->times(2)->andReturn($mockUploadBuilder);
        $mockUploadBuilder->shouldReceive('setSource')->times(2)->andReturn($mockUploadBuilder);
        $mockUploadBuilder->shouldReceive('build')->times(2)->andReturn($mockTransfer);

        $adapter = new Adapter($mockS3Client, 'bucketname', 'prefix', array(), $mockUploadBuilder);
        $temp    = tmpfile();
        fwrite($temp, "some content");
        $adapter->writeStream(
            'something',
            $temp,
            array(
                'visibility' => 'private',
                'mimetype'   => 'text/plain',
                'Expires'    => 'it does',
                'Metadata' => array(),
            )
        );
        $adapter->updateStream('something', $temp);
        fclose($temp);
    }

    public function testWriteStreamAboveLimitFail()
    {
        $mockS3Client = $this->getS3Client();
        $mockTransfer = $this->getAbstractTransfer();
        $mockTransfer->shouldReceive('upload')->andThrow(Mockery::mock('Aws\Common\Exception\MultipartUploadException'));
        $mockTransfer->shouldReceive('abort')->once();
        $mockUploadBuilder = $this->getUploadBuilder();
        $mockUploadBuilder->shouldReceive('setBucket')->once()->andReturn($mockUploadBuilder);
        $mockUploadBuilder->shouldReceive('setKey')->once()->andReturn($mockUploadBuilder);
        $mockUploadBuilder->shouldReceive('setMinPartSize')->once()->andReturn($mockUploadBuilder);
        $mockUploadBuilder->shouldReceive('setOption')->andReturn($mockUploadBuilder);
        $mockUploadBuilder->shouldReceive('setConcurrency')->once()->andReturn($mockUploadBuilder);
        $mockUploadBuilder->shouldReceive('setSource')->once()->andReturn($mockUploadBuilder);
        $mockUploadBuilder->shouldReceive('build')->once()->andReturn($mockTransfer);

        $adapter = new Adapter($mockS3Client, 'bucketname', 'prefix', array(), $mockUploadBuilder);
        $temp    = tmpfile();
        fwrite($temp, "some content");
        $adapter->writeStream(
            'something',
            $temp,
            array(
                'visibility' => 'private',
                'mimetype'   => 'text/plain',
                'Expires'    => 'it does',
                'Metadata' => array(),
            )
        );
        fclose($temp);
    }

    public function testWriteStreamBelowLimit()
    {
        $mockS3Client = $this->getS3Client();
        $mockS3Client->shouldReceive('putObject')->times(2);

        $adapter = new Adapter($mockS3Client, 'bucketname', 'prefix', array(
            'Multipart' => 10 * 1024 * 1024,
        ));

        $temp    = tmpfile();
        fwrite($temp, $content = "some content");
        $adapter->writeStream(
            'something',
            $temp,
            $options = array(
                'visibility' => 'private',
                'mimetype'   => 'text/plain',
                'Expires'    => 'it does',
                'streamsize' => 5,
            )
        );
        $adapter->updateStream('something', $temp, $options);
        fclose($temp);
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

    public function testCopy()
    {
        $mock = $this->getS3Client();
        $mock->shouldReceive('copyObject')->once()->andReturn(Mockery::self());
        $mock->shouldReceive('getAll')->once()->andReturn(array('Key' => 'something', 'LastModified' => '2011-01-01'));
        $adapter = new Adapter($mock, 'bucketname');
        $result = $adapter->copy('old', 'new');
        $this->assertArrayHasKey('path', $result);
        $this->assertContains('new', $result);
        $this->assertInternalType('int', $result['timestamp']);
    }

    public function testDeleteDir()
    {
        $mock = $this->getS3Client();
        $mock->shouldReceive('deleteMatchingObjects')->with('bucketname', 'some/dirname/')->once()->andReturn(true);
        $adapter = new Adapter($mock, 'bucketname');
        $result = $adapter->deleteDir('some/dirname');
        $this->assertTrue($result);
    }

    public function testListContents()
    {
        $mock = $this->getS3Client();
        $result = new \ArrayIterator(array(
            array('Key' => 'file.ext', 'ContentLength' => 20, 'ContentType' => 'text/plain'),
            array('Key' => 'path/to_another/dir/'),
        ));
        $mock->shouldReceive('getIterator')->once()->andReturn($result);
        $adapter = new Adapter($mock, 'bucketname');
        $listing = $adapter->listContents();
        $this->assertCount(4, $listing);
        $first = reset($listing);
        $this->assertArrayHasKey('path', $first);
        $this->assertArrayHasKey('type', $first);
        $this->assertArrayHasKey('mimetype', $first);
        $last = end($listing);
        $this->assertArrayHasKey('path', $first);
        $this->assertArrayHasKey('type', $first);
        $this->assertEquals($last['type'], 'dir');

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
        $mock = $this->getS3Client();
        $mock->shouldReceive('putObject')->once();
        $adapter = new Adapter($mock, 'bucketname');
        $result = $adapter->createDir('something');
        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('something', $result['path']);
        $this->assertEquals('dir', $result['type']);
    }

    public function testCreateDirFail()
    {
        $mock = $this->getS3Client();
        $mock->shouldReceive('putObject')->andReturn(false);
        $adapter = new Adapter($mock, 'bucketname');
        $result = $adapter->createDir('something');
        $this->assertFalse($result);
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
