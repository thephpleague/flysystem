<?php

use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;

class MountManagerTests extends PHPUnit_Framework_TestCase
{
    public function testInstantiable()
    {
        $manager = new MountManager;
    }

    public function testConstructorInjection()
    {
        $mock = Mockery::mock(
            'League\Flysystem\Filesystem',
            array(),
            array(Mockery::mock('League\Flysystem\Adapter\AbstractAdapter')->makePartial())
        );
        $mock->makePartial();
        $manager = new MountManager(array(
            'prefix' => $mock,
        ));
        $this->assertEquals($mock, $manager->getFilesystem('prefix'));
    }

    /**
     * @expectedException  InvalidArgumentException
     */
    public function testInvalidPrefix()
    {
        $manager = new MountManager;
        $manager->mountFilesystem(false, Mockery::mock('League\Flysystem\FilesystemInterface'));
    }

    /**
     * @expectedException  LogicException
     */
    public function testUndefinedFilesystem()
    {
        $manager = new MountManager;
        $manager->getFilesystem('prefix');
    }

    public function invalidCallProvider()
    {
        return array(
            array(array(), 'LogicException'),
            array(array(false), 'InvalidArgumentException'),
            array(array('path/without/protocol'), 'InvalidArgumentException'),
        );
    }

    /**
     * @dataProvider  invalidCallProvider
     */
    public function testInvalidArguments($arguments, $exception)
    {
        $this->setExpectedException($exception);
        $manager = new MountManager;
        $manager->filterPrefix($arguments);
    }

    public function testCallForwarder()
    {
        $manager = new MountManager;
        $mock = Mockery::mock('League\Flysystem\Filesystem');
        $mock->makePartial();
        $mock->shouldReceive('aMethodCall')->once()->andReturn('a result');
        $manager->mountFilesystem('prot', $mock);
        $this->assertEquals($manager->aMethodCall('prot://file.ext'), 'a result');
    }

    public function testCopyBetweenFilesystems()
    {
        $manager = new MountManager;
        $fs1 = Mockery::mock('League\Flysystem\Filesystem')->makePartial();
        $fs2 = Mockery::mock('League\Flysystem\Filesystem')->makePartial();
        $manager->mountFilesystem('fs1', $fs1);
        $manager->mountFilesystem('fs2', $fs2);

        $filename = 'test.txt';
        $buffer = tmpfile();
        $fs1->shouldReceive('readStream')->once()->with($filename)->andReturn($buffer);
        $fs2->shouldReceive('writeStream')->once()->with($filename, $buffer)->andReturn(true);
        $response = $manager->copy("fs1://{$filename}", "fs2://{$filename}");
        $this->assertTrue($response);

        // test failed status
        $fs1->shouldReceive('readStream')->once()->with($filename)->andReturn(false);
        $status = $manager->copy("fs1://{$filename}", "fs2://{$filename}");
        $this->assertFalse($status);

        $buffer = tmpfile();
        $fs1->shouldReceive('readStream')->once()->with($filename)->andReturn($buffer);
        $fs2->shouldReceive('writeStream')->once()->with($filename, $buffer)->andReturn(false);
        $status = $manager->copy("fs1://{$filename}", "fs2://{$filename}");
        $this->assertFalse($status);

        $buffer = tmpfile();
        $fs1->shouldReceive('readStream')->once()->with($filename)->andReturn($buffer);
        $fs2->shouldReceive('writeStream')->once()->with($filename, $buffer)->andReturn(true);
        $status = $manager->copy("fs1://{$filename}", "fs2://{$filename}");
        $this->assertTrue($status);
    }

    public function testMoveBetweenFilesystems()
    {
        $manager = Mockery::mock('League\Flysystem\MountManager')->makePartial();
        $fs1 = Mockery::mock('League\Flysystem\Filesystem')->makePartial();
        $fs2 = Mockery::mock('League\Flysystem\Filesystem')->makePartial();
        $manager->mountFilesystem('fs1', $fs1);
        $manager->mountFilesystem('fs2', $fs2);

        $filename = 'test.txt';
        $buffer = tmpfile();
        $fs1->shouldReceive('readStream')->with($filename)->andReturn($buffer);
        $fs2->shouldReceive('writeStream')->with($filename, $buffer)->andReturn(false);
        $code = $manager->move("fs1://{$filename}", "fs2://{$filename}");
        $this->assertFalse($code);

        $manager->shouldReceive('copy')->with("fs1://{$filename}", "fs2://{$filename}")->andReturn(true);
        $manager->shouldReceive('delete')->with("fs1://{$filename}")->andReturn(true);
        $code = $manager->move("fs1://{$filename}", "fs2://{$filename}");

        $this->assertTrue($code);
    }

    protected function mockFileIterator()
    {
        $files = Mockery::mock('\SplFileInfo', array(
            'getPathname' => 'path/file/test',
            'getFilename' => 'test',
            'getType' => 'file',
            'getSize' => 12361863,
            'getMTime' => (new \DateTime())->format('U')
        ), array('test'));

        return array($files);
    }

    protected function mockLocalAdapter()
    {
        $localAdapter = Mockery::mock('\League\Flysystem\Adapter\Local');
        $localAdapter->shouldAllowMockingProtectedMethods();
        $localAdapter->makePartial();
        $localAdapter->shouldReceive('getDirectoryIterator')->andReturn($this->mockFileIterator());
        $localAdapter->shouldReceive('getFilePath')->andReturnUsing(function ($file) {
            return $file->getPathname();
        });

        return $localAdapter;
    }

    protected function mockAwsS3Adapter()
    {
        $client = Mockery::mock('\Aws\S3\S3Client', array(
            'getIterator' => new ArrayIterator(array(
                array(
                    'Key' => 'test',
                    'path' => 'path/file/test',
                    'dirname' => 'path/file',
                    'timestamp' => (new \DateTime())->format('U'),
                    'type' => 'file',
                )
            ))
        ))->makePartial();
        $awsAdapter = Mockery::mock('\League\Flysystem\Adapter\AwsS3')->makePartial();
        $reflProperty = new \ReflectionProperty($awsAdapter, 'client');
        $reflProperty->setAccessible(true);
        $reflProperty->setValue($awsAdapter, $client);

        return $awsAdapter;
    }

    protected function mockAzureAdapter()
    {
        $client = Mockery::mock('\WindowsAzure\Blob\Internal\IBlob');
        $client->makePartial();
        $client->shouldReceive('listBlobs')->andReturn(
            Mockery::mock('\WindowsAzure\Blob\Models\ListBlobsResult', array('getBlobs' => array(
                Mockery::mock('\WindowsAzure\Blob\Models\Blob', array(
                    'getName' => 'path/file/test',
                    'getProperties' => Mockery::mock('\WindowsAzure\Blob\Models\BlobProperties', array(
                        'getLastModified' => new \DateTime(),
                        'getContentType' => 'file',
                        'getContentLength' => 88612836213,
                    ))
                ))
            )))
        );

        $adapter = Mockery::mock('\League\Flysystem\Adapter\Azure')->makePartial();
        $reflProperty = new \ReflectionProperty($adapter, 'client');
        $reflProperty->setAccessible(true);
        $reflProperty->setValue($adapter, $client);

        return $adapter;
    }

    protected function mockDropboxAdapter()
    {
        $client = Mockery::mock('\Dropbox\Client');
        $client->makePartial();
        $client->shouldReceive('getMetadataWithChildren')->andReturn(array(
            'contents' => array(
                array(
                    'path' => 'the/path/to/file',
                    'is_dir' => false,
                    'modified' => (new \DateTime())->format('U'),
                    'type' => 'file',
                )
            )
        ));

        $adapter = Mockery::mock('\League\Flysystem\Adapter\Dropbox')->makePartial();
        $reflProperty = new \ReflectionProperty($adapter, 'client');
        $reflProperty->setAccessible(true);
        $reflProperty->setValue($adapter, $client);

        return $adapter;
    }

    protected function mockFtpAdapter()
    {
        $adapter = Mockery::mock('\League\Flysystem\Adapter\Ftp');
        $adapter->makePartial();
        $adapter->shouldReceive('connect')->andReturnNull();

        return $adapter;
    }

    protected function mockSftpAdapter()
    {
        $adapter = Mockery::mock('\League\Flysystem\Adapter\Sftp');
        $adapter->makePartial();
        $adapter->shouldReceive('connect')->andReturnNull();
        $adapter->shouldReceive('getConnection')->andReturn(
            Mockery::mock('Net_SFTP', array(
                'disconnect' => null,
                'rawlist' => array(
                    'path/to/file' => array(
                        'size' => 8623486,
                        'mtime' => (new \DateTime())->format('U'),
                        'type' => 1,
                        'permissions' => 'drwxr--r--'
                    )
                )
            ))
        );

        return $adapter;
    }

    protected function mockGridFSAdapter()
    {
        $obj = new stdClass();
        $obj->sec = 86123861;
        $file = array(
            'uploadDate' => $obj,
            'metadata' => array(
                'mimetype' => 'text/plain'
            )
        );

        $gfsFile = Mockery::mock('\MongoGridFSFile', array(
            'getFilename' => 'test',
            'getSize' => 86234
        ));
        $reflProperty = new \ReflectionProperty($gfsFile, 'file');
        $reflProperty->setAccessible(true);
        $reflProperty->setValue($gfsFile, $file);

        $client = Mockery::mock('\MongoGridFS', array(
            'find' => array(
                $gfsFile
            )
        ));

        $adapter = Mockery::mock('\League\Flysystem\Adapter\GridFS');
        $adapter->makePartial();

        $reflProperty = new \ReflectionProperty($adapter, 'client');
        $reflProperty->setAccessible(true);
        $reflProperty->setValue($adapter, $client);

        return $adapter;
    }

    protected function mockRackSpaceAdapter()
    {
        $client = Mockery::mock('\OpenCloud\ObjectStore\Resource\Container');
        $client->shouldReceive('objectList')->andReturn(
            new ArrayIterator(array(
                Mockery::mock('\OpenCloud\ObjectStore\Resource\DataObject', array(
                    'getName' => 'test',
                    'getContentType' => 'text/plain; encoding=utf8',
                    'getLastModified' => (new \DateTime())->format('U'),
                    'getContentLength' => 826348623483
                ))
            ))
        );

        $adapter = Mockery::mock('\League\Flysystem\Adapter\Rackspace');
        $adapter->makePartial();
        $reflProperty = new \ReflectionProperty($adapter, 'container');
        $reflProperty->setAccessible(true);
        $reflProperty->setValue($adapter, $client);

        return $adapter;
    }

    protected function mockSynologyFtpAdapter()
    {
        $adapter = Mockery::mock('\League\Flysystem\Adapter\SynologyFtp');
        $adapter->makePartial();
        $adapter->shouldReceive('connect')->andReturnNull();

        return $adapter;
    }

    protected function mockWebDavAdapter()
    {
        $client = Mockery::mock('\Sabre\DAV\Client');
        $client->shouldReceive('propFind')->andReturn(
            array(
                '' => '',
                'path/to/test/file' => array(
                    '{DAV:}getcontentlength' => 872384,
                    '{DAV:}getlastmodified' => (new \DateTime())->format('U'),
                )
            )
        );

        $adapter = Mockery::mock('\League\Flysystem\Adapter\WebDav');
        $adapter->makePartial();

        $reflProperty = new \ReflectionProperty($adapter, 'client');
        $reflProperty->setAccessible(true);
        $reflProperty->setValue($adapter, $client);

        return $adapter;
    }

    protected function mockZipAdapter()
    {
        /** @var ZipArchive|\Mockery\Mock $archive */
        $archive = Mockery::mock('stdClass');
        $archive->numFiles = 1;
        $archive->shouldReceive('statIndex')->andReturn(array(
            'name' => 'path/to/file/test'
        ));

        $reflProperty = new \ReflectionProperty($archive, 'numFiles');
        $reflProperty->setAccessible(true);
        $reflProperty->setValue($archive, 10);

        $adapter = Mockery::mock('\League\Flysystem\Adapter\Zip');
        $adapter->makePartial();
        $adapter->shouldAllowMockingProtectedMethods();
        $adapter->shouldReceive('reopenArchive')->andReturnNull();

        $reflProperty = new \ReflectionProperty($adapter, 'archive');
        $reflProperty->setAccessible(true);
        $reflProperty->setValue($adapter, $archive);

        return $adapter;
    }

    protected function mockPassthruCache()
    {
        $cache = Mockery::mock('\League\Flysystem\Cache\Memory', array(
            'isComplete' => false,
        ));
        $cache->makePartial();
        $cache->shouldReceive('storeContents')->andReturnUsing(function ($directory, $contents, $recursive) {
            return $contents;
        });

        return $cache;
    }

    public function testFileWithAliasWithMountManager()
    {
        $adapters = array(
            'local' => new Filesystem($this->mockLocalAdapter(), $this->mockPassthruCache()),
            'aws' => new Filesystem($this->mockAwsS3Adapter(), $this->mockPassthruCache()),
            'azure' => new Filesystem($this->mockAzureAdapter(), $this->mockPassthruCache()),
            'dropbox' => new Filesystem($this->mockDropboxAdapter(), $this->mockPassthruCache()),
            'ftp' => new Filesystem($this->mockFtpAdapter(), $this->mockPassthruCache()),
            'sftp' => new Filesystem($this->mockSftpAdapter(), $this->mockPassthruCache()),
            'gridfs' => new Filesystem($this->mockGridFSAdapter(), $this->mockPassthruCache()),
            'rackspace' => new Filesystem($this->mockRackSpaceAdapter(), $this->mockPassthruCache()),
            'synologyftp' => new Filesystem($this->mockSynologyFtpAdapter(), $this->mockPassthruCache()),
            'webdav' => new Filesystem($this->mockWebDavAdapter(), $this->mockPassthruCache()),
            'zip' => new Filesystem($this->mockZipAdapter(), $this->mockPassthruCache()),
            // add your own here if you want
        );

        $mountManager = new MountManager($adapters);

        foreach (array_keys($adapters) as $prefix) {
            $results = $mountManager->listContents("{$prefix}://mount_manager.test");
            foreach ($results as $result) {
                $this->assertArrayHasKey('filesystem', $result);
                $this->assertEquals($result['filesystem'], $prefix);
            }
        }
    }
}
