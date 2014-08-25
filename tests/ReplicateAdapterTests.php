<?php

namespace League\Flysystem\Adapter;

use Mockery;
use Mockery\MockInterface;

class ReplicateAdapterTests extends \PHPUnit_Framework_TestCase
{
    public function teardown()
    {
        $cleanupAdapter = new Local(__DIR__ . '/files');
        $cleanupAdapter->deleteDir('replica');
        $cleanupAdapter->deleteDir('source');
    }

    public function testWrite()
    {
        $filename = 'replicate_test_file.txt';
        $fileContent = 'content';

        $adapter = $this->getReplicateAdapter();

        $adapter->write($filename, $fileContent);

        $sourceContents = $adapter->getSourceAdapter()->read($filename);

        $this->assertEquals($fileContent, $sourceContents['contents']);
        $this->assertEquals($filename, $sourceContents['path']);
        $this->assertEquals($sourceContents, $adapter->getReplicaAdapter()->read($filename));
    }

    public function testWriteFail()
    {
        $filename = 'replicate_test_file.txt';
        $fileContent = 'content';

        $mockedAdapter = $this->getMockedAdapter();
        $mockedAdapter->shouldReceive('write')
            ->with($filename, $fileContent, null)
            ->once()
            ->andReturn(false);

        $adapter = $this->getReplicateAdapter($mockedAdapter);

        $result = $adapter->write($filename, $fileContent);

        $this->assertFalse($result);
    }

    public function testUpdate()
    {
        $filename = 'replicate_test_file.txt';
        $fileContent = 'content';
        $fileContentNew = 'newcontent';

        $adapter = $this->getReplicateAdapter();

        $adapter->write($filename, $fileContent);

        $sourceContents = $adapter->getSourceAdapter()->read($filename);

        $this->assertEquals($fileContent, $sourceContents['contents']);
        $this->assertEquals($sourceContents, $adapter->getReplicaAdapter()->read($filename));

        $adapter->update($filename, $fileContentNew);

        $sourceContents = $adapter->getSourceAdapter()->read($filename);

        $this->assertEquals($fileContentNew, $sourceContents['contents']);
        $this->assertEquals($sourceContents, $adapter->getReplicaAdapter()->read($filename));
    }

    public function testUpdateFail()
    {
        $filename = 'replicate_test_file.txt';
        $fileContent = 'newcontent';

        $mockedAdapter = $this->getMockedAdapter();
        $mockedAdapter->shouldReceive('update')
            ->with($filename, $fileContent, null)
            ->once()
            ->andReturn(false);

        $adapter = $this->getReplicateAdapter($mockedAdapter);

        $result = $adapter->update($filename, $fileContent);

        $this->assertFalse($result);
    }

    public function testRename()
    {
        $filename = 'replicate_test_file.txt';
        $filenameNew = 'new_replicate_test_file.txt';
        $fileContent = 'content';

        $adapter = $this->getReplicateAdapter();

        $adapter->write($filename, $fileContent);

        $this->assertTrue($adapter->getSourceAdapter()->has($filename));
        $this->assertTrue($adapter->getReplicaAdapter()->has($filename));

        $adapter->rename($filename, $filenameNew);

        $this->assertTrue($adapter->getSourceAdapter()->has($filenameNew));
        $this->assertTrue($adapter->getReplicaAdapter()->has($filenameNew));

        $this->assertFalse($adapter->getSourceAdapter()->has($filename));
        $this->assertFalse($adapter->getReplicaAdapter()->has($filename));
    }

    public function testRenameFail()
    {
        $filename = 'replicate_test_file.txt';
        $filenameNew = 'new_replicate_test_file.txt';

        $mockedAdapter = $this->getMockedAdapter();
        $mockedAdapter->shouldReceive('rename')
            ->with($filename, $filenameNew)
            ->once()
            ->andReturn(false);

        $adapter = $this->getReplicateAdapter($mockedAdapter);

        $result = $adapter->rename($filename, $filenameNew);

        $this->assertFalse($result);
    }

    public function testDelete()
    {
        $filename = 'replicate_test_file.txt';
        $fileContent = 'content';

        $adapter = $this->getReplicateAdapter();

        $adapter->write($filename, $fileContent);

        $this->assertTrue($adapter->getSourceAdapter()->has($filename));
        $this->assertTrue($adapter->getReplicaAdapter()->has($filename));

        $adapter->delete($filename);

        $this->assertFalse($adapter->getSourceAdapter()->has($filename));
        $this->assertFalse($adapter->getReplicaAdapter()->has($filename));
    }

    public function testDeleteFail()
    {
        $filename = 'replicate_test_file.txt';

        $mockedAdapter = $this->getMockedAdapter();
        $mockedAdapter->shouldReceive('delete')
            ->with($filename)
            ->once()
            ->andReturn(false);

        $adapter = $this->getReplicateAdapter($mockedAdapter);

        $result = $adapter->delete($filename);

        $this->assertFalse($result);
    }

    public function testCreateDir()
    {
        $subdir = 'subdir';

        $adapter = $this->getReplicateAdapter();

        $adapter->createDir($subdir);

        $dirFilter = function ($object) use ($subdir) {
            return $object['type'] === 'dir' && $object['path'] === $subdir;
        };

        $sourceContents = $adapter->getSourceAdapter()->listContents();
        $replicaContents = $adapter->getReplicaAdapter()->listContents();

        $this->assertNotEmpty(array_filter($sourceContents, $dirFilter));
        $this->assertNotEmpty(array_filter($replicaContents, $dirFilter));
    }

    public function testCreateDirFail()
    {
        $subdir = 'subdir';

        $mockedAdapter = $this->getMockedAdapter();
        $mockedAdapter->shouldReceive('createDir')
            ->with($subdir, null)
            ->once()
            ->andReturn(false);

        $adapter = $this->getReplicateAdapter($mockedAdapter);

        $result = $adapter->createDir($subdir);

        $this->assertFalse($result);
    }

    public function testDeleteDir()
    {
        $subdir = 'subdir';

        $adapter = $this->getReplicateAdapter();

        $adapter->createDir($subdir);

        $adapter->deleteDir($subdir);

        $dirFilter = function ($object) use ($subdir) {
            return $object['type'] === 'dir' && $object['path'] === $subdir;
        };

        $sourceContents = $adapter->getSourceAdapter()->listContents();
        $replicaContents = $adapter->getReplicaAdapter()->listContents();

        $this->assertEmpty(array_filter($sourceContents, $dirFilter));
        $this->assertEmpty(array_filter($replicaContents, $dirFilter));
    }

    public function testDeleteDirFail()
    {
        $subdir = 'subdir';

        $mockedAdapter = $this->getMockedAdapter();
        $mockedAdapter->shouldReceive('deleteDir')
            ->with($subdir)
            ->once()
            ->andReturn(false);

        $adapter = $this->getReplicateAdapter($mockedAdapter);

        $result = $adapter->deleteDir($subdir);

        $this->assertFalse($result);
    }

    public function testHas()
    {
        $filename = 'replicate_test_file.txt';

        $mockedSourceAdapter = $this->getMockedAdapter();
        $mockedSourceAdapter->shouldReceive('has')
            ->with($filename)
            ->once();

        $mockedReplicaAdapter = $this->getMockedAdapter();
        $mockedReplicaAdapter->shouldReceive('has')
            ->never();

        $adapter = $this->getReplicateAdapter($mockedSourceAdapter, $mockedReplicaAdapter);

        $adapter->has($filename);
    }

    public function testRead()
    {
        $filename = 'replicate_test_file.txt';

        $mockedSourceAdapter = $this->getMockedAdapter();
        $mockedSourceAdapter->shouldReceive('read')
            ->with($filename)
            ->once();

        $mockedReplicaAdapter = $this->getMockedAdapter();
        $mockedReplicaAdapter->shouldReceive('read')
            ->never();

        $adapter = $this->getReplicateAdapter($mockedSourceAdapter, $mockedReplicaAdapter);

        $adapter->read($filename);
    }

    public function testListContents()
    {
        $directory = '';
        $recursive = false;

        $mockedSourceAdapter = $this->getMockedAdapter();
        $mockedSourceAdapter->shouldReceive('listContents')
            ->with($directory, $recursive)
            ->once();

        $mockedReplicaAdapter = $this->getMockedAdapter();
        $mockedReplicaAdapter->shouldReceive('read')
            ->never();

        $adapter = $this->getReplicateAdapter($mockedSourceAdapter, $mockedReplicaAdapter);

        $adapter->listContents($directory, $recursive);
    }

    public function testGetMetadata()
    {
        $filename = 'replicate_test_file.txt';

        $mockedSourceAdapter = $this->getMockedAdapter();
        $mockedSourceAdapter->shouldReceive('getMetadata')
            ->with($filename)
            ->once();

        $mockedReplicaAdapter = $this->getMockedAdapter();
        $mockedReplicaAdapter->shouldReceive('getMetadata')
            ->never();

        $adapter = $this->getReplicateAdapter($mockedSourceAdapter, $mockedReplicaAdapter);

        $adapter->getMetadata($filename);
    }

    public function testGetSize()
    {
        $filename = 'replicate_test_file.txt';

        $mockedSourceAdapter = $this->getMockedAdapter();
        $mockedSourceAdapter->shouldReceive('getSize')
            ->with($filename)
            ->once();

        $mockedReplicaAdapter = $this->getMockedAdapter();
        $mockedReplicaAdapter->shouldReceive('getSize')
            ->never();

        $adapter = $this->getReplicateAdapter($mockedSourceAdapter, $mockedReplicaAdapter);

        $adapter->getSize($filename);
    }

    public function testGetMimetype()
    {
        $filename = 'replicate_test_file.txt';

        $mockedSourceAdapter = $this->getMockedAdapter();
        $mockedSourceAdapter->shouldReceive('getMimetype')
            ->with($filename)
            ->once();

        $mockedReplicaAdapter = $this->getMockedAdapter();
        $mockedReplicaAdapter->shouldReceive('getMimetype')
            ->never();

        $adapter = $this->getReplicateAdapter($mockedSourceAdapter, $mockedReplicaAdapter);

        $adapter->getMimetype($filename);
    }

    public function testGetTimestamp()
    {
        $filename = 'replicate_test_file.txt';

        $mockedSourceAdapter = $this->getMockedAdapter();
        $mockedSourceAdapter->shouldReceive('getTimestamp')
            ->with($filename)
            ->once();

        $mockedReplicaAdapter = $this->getMockedAdapter();
        $mockedReplicaAdapter->shouldReceive('getTimestamp')
            ->never();

        $adapter = $this->getReplicateAdapter($mockedSourceAdapter, $mockedReplicaAdapter);

        $adapter->getTimestamp($filename);
    }
    
    /**
     * @param mixed $source
     * @param mixed $replica
     *
     * @return ReplicateAdapter
     */
    protected function getReplicateAdapter($source = null, $replica = null)
    {
        if (!$source) {
            $source = new Local(__DIR__ . '/files/source');
        }

        if (!$replica) {
            $replica = new Local(__DIR__ . '/files/replica');
        }

        return new ReplicateAdapter($source, $replica);
    }

    /**
     * @return MockInterface
     */
    protected function getMockedAdapter()
    {
        return Mockery::mock(
            '\League\Flysystem\Adapter\Local[write,update,rename,delete,deleteDir,createDir,has,read,listContents,getMetadata,getSize,getMimetype,getTimestamp]',
            array(
                __DIR__ . '/files'
            )
        );
    }
}
