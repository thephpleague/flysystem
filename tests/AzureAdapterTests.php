<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Config;
use Mockery;
use WindowsAzure\Blob\Models\CopyBlobResult;
use WindowsAzure\Blob\Models\GetBlobResult;
use WindowsAzure\Common\Internal\Resources;
use WindowsAzure\Common\ServiceException;

class AzureAdapterTests extends \PHPUnit_Framework_TestCase
{
    const CONTAINER_NAME = 'test-container';

    private $adapter;
    private $azure;

    protected function getAzureClient()
    {
        return Mockery::mock('WindowsAzure\Blob\Internal\IBlob');
    }

    protected function getCopyBlobResult($lastModified)
    {
        return CopyBlobResult::create([
            Resources::LAST_MODIFIED => $lastModified,
        ]);
    }

    protected function getReadBlobResult($lastModified, $contentString)
    {
        return GetBlobResult::create([
            Resources::LAST_MODIFIED  => $lastModified,
            Resources::CONTENT_LENGTH => strlen($contentString),
        ], $contentString, []);
    }

    protected function getStreamFromString($string)
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $string);
        rewind($stream);

        return $stream;
    }

    public function setUp()
    {
        $this->azure = $this->getAzureClient();

        $this->adapter = new Azure($this->azure, self::CONTAINER_NAME);
    }

    public function testWrite()
    {
        $resultBlob = $this->getCopyBlobResult('Tue, 02 Dec 2014 08:09:01 +0000');
        $this->azure->shouldReceive('createBlockBlob')->once()->andReturn($resultBlob);

        $this->assertSame([
            'path'      => 'bar/foo.txt',
            'timestamp' => 1417507741,
            'dirname'   => 'bar',
            'type'      => 'file',
            'contents'  => 'content',
        ], $this->adapter->write('bar/foo.txt', 'content', new Config()));
    }

    public function testUpdate()
    {
        $resultBlob = $this->getCopyBlobResult('Tue, 02 Dec 2014 08:09:01 +0000');
        $this->azure->shouldReceive('createBlockBlob')->once()->andReturn($resultBlob);

        $this->assertSame([
            'path'      => 'bar/foo.txt',
            'timestamp' => 1417507741,
            'dirname'   => 'bar',
            'type'      => 'file',
            'contents'  => 'content',
        ], $this->adapter->update('bar/foo.txt', 'content', new Config()));
    }

    public function testWriteStream()
    {
        $stream = $this->getStreamFromString('content');
        $resultBlob = $this->getCopyBlobResult('Tue, 02 Dec 2014 08:09:01 +0000');

        $this->azure->shouldReceive('createBlockBlob')->once()->andReturn($resultBlob);

        $this->assertSame([
            'path'      => 'bar/foo.txt',
            'timestamp' => 1417507741,
            'dirname'   => 'bar',
            'type'      => 'file',
        ], $this->adapter->writeStream('bar/foo.txt', $stream, new Config()));
    }

    public function testUpdateStream()
    {
        $stream = $this->getStreamFromString('content');
        $resultBlob = $this->getCopyBlobResult('Tue, 02 Dec 2014 08:09:01 +0000');

        $this->azure->shouldReceive('createBlockBlob')->once()->andReturn($resultBlob);

        $this->assertSame([
            'path'      => 'bar/foo.txt',
            'timestamp' => 1417507741,
            'dirname'   => 'bar',
            'type'      => 'file',
        ], $this->adapter->updateStream('bar/foo.txt', $stream, new Config()));
    }

    public function testRead()
    {
        $resultBlob = $this->getReadBlobResult('Tue, 02 Dec 2014 08:09:01 +0000', 'foo bar');

        $this->azure->shouldReceive('getBlob')->once()->andReturn($resultBlob);

        $this->assertSame([
            'path'      => 'bar/foo.txt',
            'timestamp' => 1417507741,
            'dirname'   => 'bar',
            'mimetype'  => null,
            'size'      => 7,
            'type'      => 'file',
            'contents'  => 'foo bar',
        ], $this->adapter->read('bar/foo.txt', new Config()));
    }

    public function testReadStream()
    {
        $resultBlob = $this->getReadBlobResult('Tue, 02 Dec 2014 08:09:01 +0000', 'foo bar');

        $this->azure->shouldReceive('getBlob')->once()->andReturn($resultBlob);
        $result = $this->adapter->readStream('bar/foo.txt', new Config());

        $this->assertArrayHasKey('stream', $result);
        $this->assertTrue(is_resource($result['stream']));

        unset($result['stream']);

        $this->assertSame([
            'path'      => 'bar/foo.txt',
            'timestamp' => 1417507741,
            'dirname'   => 'bar',
            'mimetype'  => null,
            'size'      => 7,
            'type'      => 'file',
        ], $result);
    }

    public function testGetMetadata()
    {
        $resultBlob = $this->getReadBlobResult('Tue, 02 Dec 2014 08:09:01 +0000', 'foo bar');

        $this->azure->shouldReceive('getBlob')->andReturn($resultBlob);

        $expectedResult = [
            'path'      => 'bar/foo.txt',
            'timestamp' => 1417507741,
            'dirname'   => 'bar',
            'mimetype'  => null,
            'size'      => 7,
            'type'      => 'file',
        ];

        $this->assertSame($expectedResult, $this->adapter->getMetadata('bar/foo.txt'));
        $this->assertSame($expectedResult, $this->adapter->getTimestamp('bar/foo.txt'));
        $this->assertSame($expectedResult, $this->adapter->getMimetype('bar/foo.txt'));
        $this->assertSame($expectedResult, $this->adapter->getSize('bar/foo.txt'));
    }

    public function testHasWhenFileExists()
    {
        $this->azure->shouldReceive('getBlob')->once()->andReturn(true);

        $this->assertTrue($this->adapter->has('foo.txt'));
    }

    public function testHasWhenFileDoesNotExist()
    {
        $this->azure->shouldReceive('getBlob')->andThrow(new ServiceException(404));

        $this->assertFalse($this->adapter->has('foo.txt'));
    }

    /**
     * @expectedException WindowsAzure\Common\ServiceException
     */
    public function testHasWhenError()
    {
        $this->azure->shouldReceive('getBlob')->andThrow(new ServiceException(500));

        $this->adapter->has('foo.txt');
    }

    public function testCreateDir()
    {
        $this->assertSame([
            'path' => 'foo-dir',
            'type' => 'dir',
        ], $this->adapter->createDir('foo-dir', new Config()));
    }

    public function testCopy()
    {
        $this->azure->shouldReceive('copyBlob')->once()->andReturn(true);

        $this->assertTrue($this->adapter->copy('from.txt', 'to.txt'));
    }

    public function testRename()
    {
        $this->azure->shouldReceive('copyBlob')->once()->andReturn(true);
        $this->azure->shouldReceive('deleteBlob')->once()->andReturn(true);

        $this->assertTrue($this->adapter->rename('from.txt', 'to.txt'));
    }

    public function testDelete()
    {
        $this->azure->shouldReceive('deleteBlob')->once()->andReturn(true);

        $this->assertTrue($this->adapter->delete('file.txt'));
    }

    public function testDeleteDir()
    {
        $blob = Mockery::mock('WindowsAzure\Blob\Models\Blob');
        $blob->shouldReceive('getName')->once();

        $blobsList = Mockery::mock('WindowsAzure\Blob\Models\ListBlobsResult');
        $blobsList->shouldReceive('getBlobs')->once()->andReturn([$blob]);

        $this->azure->shouldReceive('listBlobs')->once()->andReturn($blobsList);
        $this->azure->shouldReceive('deleteBlob')->once();

        $this->assertTrue($this->adapter->deleteDir('dir'));
    }

    public function testListContents()
    {
        $properties = Mockery::mock('WindowsAzure\Blob\Models\BlobProperties');
        $properties->shouldReceive('getLastModified')->once()->andReturn(\DateTime::createFromFormat(\DateTime::RFC1123, 'Tue, 02 Dec 2014 08:09:01 +0000'));
        $properties->shouldReceive('getContentType')->once()->andReturn('text/plain');
        $properties->shouldReceive('getContentLength')->once()->andReturn(42);

        $blob = Mockery::mock('WindowsAzure\Blob\Models\Blob');
        $blob->shouldReceive('getName')->once()->andReturn('foo.txt');
        $blob->shouldReceive('getProperties')->once()->andReturn($properties);

        $blobsList = Mockery::mock('WindowsAzure\Blob\Models\ListBlobsResult');
        $blobsList->shouldReceive('getBlobs')->once()->andReturn([$blob]);

        $this->azure->shouldReceive('listBlobs')->once()->andReturn($blobsList);

        $this->assertSame([
            [
                'path'      => 'foo.txt',
                'timestamp' => 1417507741,
                'dirname'   => '',
                'mimetype'  => 'text/plain',
                'size'      => 42,
                'type'      => 'file',
            ],
        ], $this->adapter->listContents());
    }
}
