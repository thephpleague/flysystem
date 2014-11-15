<?php

use League\Flysystem\Adapter\GridFS as Adapter;
use League\Flysystem\Config;

class GridFSTests extends PHPUnit_Framework_TestCase
{
    const FILE_ID           = 24;
    const FILE_CREATED_AT   = 42;

    protected function getClient()
    {
        return Mockery::mock('MongoGridFs');
    }

    protected function getMongoFile(array $data = array(), $content = null)
    {
        if (!class_exists('MongoRegex')) {
            $file = Mockery::mock('MongoGridFSFile')->shouldIgnoreMissing();

            if ($content !== null) {
                $file->shouldReceive('getBytes')->andReturn($content);
            }
        } else {
            $file = $this->getMockBuilder('MongoGridFSFile')->disableOriginalConstructor()->getMock();
            if ($content !== null) {
                $file
                    ->expects($this->once())
                    ->method('getBytes')
                    ->willReturn($content);
            }
        }

        $file->file = array_merge(array(
            '_id'           => self::FILE_ID,
            'uploadDate'    => (object) array(
                'sec' => self::FILE_CREATED_AT,
            ),
        ), $data);

        return $file;
    }

    protected function getMongoGridFSException()
    {
        if (!class_exists('MongoGridFSException')) {
            eval('class MongoGridFSException extends RuntimeException {}');
        }

        return new MongoGridFSException();
    }

    public function testGetClient()
    {
        $client= $this->getClient();
        $adapter = new Adapter($client);

        $this->assertSame($client, $adapter->getClient());
    }

    public function testHas()
    {
        $client= $this->getClient();
        $adapter = new Adapter($client);

        $client->shouldReceive('findOne')->once()->andReturn('something not null');

        $this->assertTrue($adapter->has('something'));
    }

    public function testHasWhenFileDoesntExist()
    {
        $client= $this->getClient();
        $adapter = new Adapter($client);

        $client->shouldReceive('findOne')->once()->andReturn(null);

        $this->assertFalse($adapter->has('something'));
    }

    public function testWriteAndUpdate()
    {
        $client= $this->getClient();
        $adapter = new Adapter($client);
        $file = $this->getMongoFile(array(
            'metadata'      => array(
                'mimetype' => 'text/plain'
            ),
        ));

        $client->shouldReceive('storeBytes')->times(2)->andReturn('some_id');
        $client->shouldReceive('findOne')->times(2)->andReturn($file);

        $expectedResult = array(
            'path'      => 'file.txt',
            'type'      => 'file',
            'size'      => null,
            'timestamp' => self::FILE_CREATED_AT,
            'dirname'   => '',
            'mimetype'  => 'text/plain'
        );

        $this->assertSame($expectedResult, $adapter->write('file.txt', 'content', new Config()));
        $this->assertSame($expectedResult, $adapter->update('file.txt', 'content', new Config()));
    }

    public function testMimeTypeCanBeOverridenOnWrite()
    {
        $client= $this->getClient();
        $adapter = new Adapter($client);
        $file = $this->getMongoFile();

        $client->shouldReceive('storeBytes')->once()->with('content', array('filename' => 'file.txt', 'metadata' => array('mimetype' => 'application/json')))->andReturn('some_id');
        $client->shouldReceive('findOne')->once()->andReturn($file);

        $expectedResult = array(
            'path'      => 'file.txt',
            'type'      => 'file',
            'size'      => null,
            'timestamp' => self::FILE_CREATED_AT,
            'dirname'   => '',
        );

        $this->assertSame($expectedResult, $adapter->write('file.txt', 'content', new Config(array(
            'mimetype' => 'application/json'
        ))));
    }

    public function testWriteHandleErrors()
    {
        $client= $this->getClient();
        $adapter = new Adapter($client);
        $file = $this->getMongoFile();

        $client->shouldReceive('storeBytes')->once()->andThrow($this->getMongoGridFSException());

        $this->assertFalse($adapter->write('file.txt', 'content', new Config()));
    }

    public function testWriteStreamAndUpdateStream()
    {
        $client= $this->getClient();
        $adapter = new Adapter($client);
        $file = $this->getMongoFile();
        $stream = fopen('php://memory', 'r');

        $client->shouldReceive('storeFile')->times(2)->andReturn('some_id');
        $client->shouldReceive('findOne')->times(2)->andReturn($file);

        $expectedResult = array(
            'path'      => 'file.txt',
            'type'      => 'file',
            'size'      => null,
            'timestamp' => self::FILE_CREATED_AT,
            'dirname'   => '',
        );

        $this->assertSame($expectedResult, $adapter->writeStream('file.txt', $stream, new Config()));
        $this->assertSame($expectedResult, $adapter->updateStream('file.txt', $stream, new Config()));
    }

    public function testGetMetadata()
    {
        $client= $this->getClient();
        $adapter = new Adapter($client);
        $file = $this->getMongoFile(array(
            'metadata' => array(
                'mimetype' => 'text/plain',
            )
        ));

        $client->shouldReceive('findOne')->times(4)->andReturn($file);

        $expectedResult = array(
            'path'      => 'file.txt',
            'type'      => 'file',
            'size'      => null,
            'timestamp' => self::FILE_CREATED_AT,
            'dirname'   => '',
            'mimetype'  => 'text/plain',
        );

        $this->assertSame($expectedResult, $adapter->getMetadata('file.txt'));
        $this->assertSame($expectedResult, $adapter->getMimetype('file.txt'));
        $this->assertSame($expectedResult, $adapter->getTimestamp('file.txt'));
        $this->assertSame($expectedResult, $adapter->getSize('file.txt'));
    }

    public function testDelete()
    {
        $client= $this->getClient();
        $adapter = new Adapter($client);
        $file = $this->getMongoFile();

        $client->shouldReceive('findOne')->once()->andReturn($file);
        $client->shouldReceive('delete')->once()->andReturn(true);

        $this->assertTrue($adapter->delete('file.txt'));
    }

    public function testDeleteWhenFileDoesNotExist()
    {
        $client= $this->getClient();
        $adapter = new Adapter($client);
        $file = $this->getMongoFile();

        $client->shouldReceive('findOne')->once()->andReturn(false);

        $this->assertFalse($adapter->delete('file.txt'));
    }

    public function testRead()
    {
        $client= $this->getClient();
        $adapter = new Adapter($client);
        $file = $this->getMongoFile(array(), 'bytes');

        $client->shouldReceive('findOne')->once()->andReturn($file);

        $this->assertEquals(array('contents' => 'bytes'), $adapter->read('file.txt'));
    }

    public function testReadStream()
    {
        $client= $this->getClient();
        $adapter = new Adapter($client);
        $file = $this->getMongoFile(array(), 'bytes');

        $client->shouldReceive('findOne')->once()->andReturn($file);

        $result = $adapter->readStream('file.txt');
        $this->assertArrayHasKey('stream', $result);
        $this->assertInternalType('resource', $result['stream']);
    }

    public function testReadWhenFileDoesntExist()
    {
        $client= $this->getClient();
        $adapter = new Adapter($client);
        $file = $this->getMongoFile();

        $client->shouldReceive('findOne')->once()->andReturn(false);

        $this->assertFalse($adapter->read('file.txt'));
    }

    public function testDeleteDir()
    {
        if (!class_exists('MongoRegex')) {
            $this->markTestSkipped('MongoDB PHP extension needs to be installed for this test.');
            return;
        }

        $client= $this->getClient();
        $adapter = new Adapter($client);
        $file = $this->getMongoFile();

        $client->shouldReceive('remove')->once()->andReturn(true);

        $this->assertTrue($adapter->deleteDir('dir'));
    }

    /**
     * @expectedException  \LogicException
     */
    public function testVisibilityCantBeSet()
    {
        $adapter = new Adapter($this->getClient());
        $adapter->setVisibility('foo.txt', 'visibility');
    }

    /**
     * @expectedException  \LogicException
     */
    public function testVisibilityCantBeGet()
    {
        $adapter = new Adapter($this->getClient());
        $adapter->getVisibility('foo.txt');
    }

    /**
     * @expectedException  \LogicException
     */
    public function testDirectoriesCantBeCreated()
    {
        $adapter = new Adapter($this->getClient());
        $adapter->createDir('dir', new Config());
    }

    /**
     * @expectedException  \BadMethodCallException
     */
    public function testContentCantBeListedRecursively()
    {
        $adapter = new Adapter($this->getClient());
        $adapter->listContents('dir', true);
    }

    public function testContentCanBeListed()
    {
        if (!class_exists('MongoRegex')) {
            $this->markTestSkipped('MongoDB PHP extension needs to be installed for this test.');
            return;
        }

        $client= $this->getClient();
        $adapter = new Adapter($client);
        $file = $this->getMongoFile();

        $client->shouldReceive('find')->once()->andReturn(array($file));
        $file
            ->expects($this->once())
            ->method('getFilename')
            ->willReturn('lala.txt');

        $this->assertSame(array(
            array(
                'path'      => 'lala.txt',
                'type'      => 'file',
                'size'      => null,
                'timestamp' => self::FILE_CREATED_AT,
                'dirname'   => '',
            ),
        ), $adapter->listContents('lala'));
    }

    public function testCopy()
    {
        $client= $this->getClient();
        $adapter = new Adapter($client);
        $file = $this->getMongoFile();

        // readStream
        $client->shouldReceive('findOne')->once()->andReturn($file);

        // writeStream
        $client->shouldReceive('storeFile')->once()->andReturn('some_id');
        $client->shouldReceive('findOne')->once()->andReturn($file);

        $this->assertTrue($adapter->copy('original.txt', 'copy.txt'));
    }

    public function testRename()
    {
        $client= $this->getClient();
        $adapter = new Adapter($client);
        $file = $this->getMongoFile();

        // readStream
        $client->shouldReceive('findOne')->once()->andReturn($file);

        // writeStream
        $client->shouldReceive('storeFile')->once()->andReturn('some_id');
        $client->shouldReceive('findOne')->once()->andReturn($file);

        // delete
        $client->shouldReceive('findOne')->once()->andReturn($file);
        $client->shouldReceive('delete')->once()->andReturn(true);

        $this->assertTrue($adapter->rename('file.txt', 'new.txt'));
    }
}
