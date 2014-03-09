<?php

namespace League\Flysystem;

class FilesystemTests extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        clearstatcache();
        $fs = new Adapter\Local(__DIR__.'/');
        $fs->deleteDir('files');
        $fs->createDir('files');
    }

    public function teardown()
    {
        $this->setup();
    }

    public function testInstantiable()
    {
        $instance = new Filesystem($adapter = new Adapter\Local(__DIR__.'/files/deeper'), $cache = new Cache\Memory);
    }

    public function filesystemProvider()
    {
        $adapter = new Adapter\Local(__DIR__.'/files');
        $cache = new Cache\Memory;
        $filesystem = new Filesystem($adapter, $cache);

        return array(
            array($filesystem, $adapter, $cache),
        );
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testListContents($filesystem)
    {
        $result = $filesystem->listContents();
        $this->assertInternalType('array', $result);
        $this->assertCount(0, $result);
        $filesystem->flushCache();
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testIsComplete($filesystem, $adapter, $cache)
    {
        $this->assertFalse($cache->isComplete('', false));
        $filesystem->listContents();
        $this->assertTrue($cache->isComplete('', false));
        $cache->flush();
        $this->assertFalse($cache->isComplete('', false));
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testDepGetters($filesystem)
    {
        $this->assertInstanceOf('League\Flysystem\CacheInterface', $filesystem->getCache());
        $this->assertInstanceOf('League\Flysystem\ReadInterface', $filesystem->getCache());
        $this->assertInstanceOf('League\Flysystem\Cache\AbstractCache', $filesystem->getCache());
        $this->assertInstanceOf('League\Flysystem\AdapterInterface', $filesystem->getAdapter());
        $this->assertInstanceOf('League\Flysystem\ReadInterface', $filesystem->getAdapter());
        $this->assertInstanceOf('League\Flysystem\Adapter\AbstractAdapter', $filesystem->getAdapter());
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testWrite(Filesystem $filesystem, $adapter, $cache)
    {
        $this->assertTrue($filesystem->write('some_file.txt', 'some content'));
        $this->assertTrue($filesystem->has('some_file.txt'));
        $this->assertTrue($cache->has('some_file.txt'));
        $this->assertTrue($adapter->has('some_file.txt'));
        $this->assertCount(1, $filesystem->listContents());
        $this->assertCount(1, $cache->listContents('', false));
        $this->assertCount(1, $adapter->listContents('', false));

        $filesystem->rename('some_file.txt', 'other_name.txt');
        $this->assertFalse($filesystem->has('some_file.txt'));
        $this->assertFalse($cache->has('some_file.txt'));
        $this->assertFalse($adapter->has('some_file.txt'));
        $this->assertTrue($filesystem->has('other_name.txt'));
        $this->assertTrue($cache->has('other_name.txt'));
        $this->assertTrue($adapter->has('other_name.txt'));
        $this->assertCount(1, $filesystem->listContents());
        $this->assertCount(1, $cache->listContents('', false));
        $this->assertCount(1, $adapter->listContents('', false));

        $filesystem->delete('other_name.txt');
        $this->assertFalse($filesystem->has('other_name.txt'));
        $this->assertFalse($cache->has('other_name.txt'));
        $this->assertFalse($adapter->has('other_name.txt'));
        $this->assertCount(0, $filesystem->listContents());
        $this->assertCount(0, $cache->listContents('', false));
        $this->assertCount(0, $adapter->listContents('', false));
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testPut(Filesystem $filesystem, $adapter, $cache)
    {
        $filesystem->flushCache();
        $this->assertFalse($filesystem->has('new_file.txt'));
        $this->assertTrue($filesystem->put('new_file.txt', 'new content'));
        $this->assertTrue($filesystem->has('new_file.txt'));
        $this->assertEquals('new content', $filesystem->read('new_file.txt'));

        $this->assertTrue($filesystem->put('new_file.txt', 'modified content'));
        $this->assertEquals('modified content', $filesystem->read('new_file.txt'));
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testPutStream(Filesystem $filesystem, $adapter, $cache)
    {
        $filesystem->flushCache();
        $stream = tmpfile();
        fwrite($stream, 'new content');
        $this->assertFalse($filesystem->has('new_file.txt'));
        $this->assertTrue($filesystem->putStream('new_file.txt', $stream));
        fclose($stream);
        unset($stream);
        $this->assertTrue($filesystem->has('new_file.txt'));
        $this->assertEquals('new content', $filesystem->read('new_file.txt'));

        $update = tmpfile();
        fwrite($update, 'modified content');
        $this->assertTrue($filesystem->putStream('new_file.txt', $update));
        $filesystem->flushCache();
        fclose($update);
        $this->assertEquals('modified content', $filesystem->read('new_file.txt'));
    }

    public function testPutFail()
    {
        $mock = \Mockery::mock('League\Flysystem\AdapterInterface');
        $mock->shouldReceive('has')->andReturn(true);
        $mock->shouldReceive('update')->andReturn(false);
        $filesystem = new Filesystem($mock);
        $this->assertFalse($filesystem->put('something', 'something'));
    }

    /**
     * @expectedException  \League\Flysystem\FileExistsException
     */
    public function testFileExists()
    {
        $filesystem = new Filesystem(new Adapter\Local(__DIR__));
        $filesystem->write('FilesystemTests.php', 'something');
    }

    /**
     * @dataProvider filesystemProvider
     * @expectedException  \League\Flysystem\FileNotFoundException
     */
    public function testFileNotFoundUpdate($filesystem)
    {
        $filesystem->update('not_found', 'content');
    }

    /**
     * @dataProvider filesystemProvider
     * @expectedException  \League\Flysystem\FileNotFoundException
     */
    public function testFileNotFoundDelete($filesystem)
    {
        $filesystem->delete('not_found');
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testImplicitDirs($filesystem, $adapter, $cache)
    {
        $this->assertCount(0, $filesystem->listContents());
        $filesystem->write('dummy.txt', 'content');
        $this->assertCount(1, $filesystem->listContents());
        $filesystem->write('nested/dir/dummy.txt', 'text');
        $this->assertCount(4, $filesystem->listContents('', true));
        $this->assertTrue($cache->isComplete('nested/dir', true));
        $filesystem->deleteDir('nested');
        $this->assertCount(1, $filesystem->listContents('', true));
        $filesystem->delete('dummy.txt');
        $this->assertCount(0, $filesystem->listContents('', true));
        $filesystem->flushCache();
    }

    public function metaProvider()
    {
        $adapter = new Adapter\Local(__DIR__.'/files');
        $cache = new Cache\Memory;
        $filesystem = new Filesystem($adapter, $cache);

        return array(
            array($filesystem, $adapter, $cache, 'getTimestamp', 'timestamp', 'int', 100),
            array($filesystem, $adapter, $cache, 'getMimetype', 'mimetype', 'string', 'plain/text'),
            array($filesystem, $adapter, $cache, 'getSize', 'size', 'int', 10),
            array($filesystem, $adapter, $cache, 'getVisibility', 'visibility', 'string', 'public'),
        );
    }

    /**
     * @dataProvider metaProvider
     */
    public function testGetters($filesystem, $adapter, $cache, $method, $key, $type, $mockValue)
    {
        $filesystem->write('test.txt', 'something');
        $cache->flush();
        $this->assertEquals('something', $filesystem->read('test.txt'));
        $value = $filesystem->{$method}('test.txt');
        $this->assertInternalType($type, $value);
        $cache->updateObject('test.txt', array($key => $mockValue));
        $this->assertEquals($mockValue, $filesystem->{$method}('test.txt'));
        $cache->flush();
        $this->assertEquals($value, $filesystem->{$method}('test.txt'));
        $filesystem->delete('test.txt');
        $cache->flush();
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testWriteReadUpdate($filesystem, $adapter, $cache)
    {
        $filesystem->write('test.txt', 'first');
        $this->assertEquals('first', $filesystem->read('test.txt'));
        $this->assertEquals('first', $cache->read('test.txt'));
        $cache->flush();
        $this->assertEquals('first', $filesystem->read('test.txt'));
        $filesystem->update('test.txt', 'second');
        $this->assertEquals('second', $filesystem->read('test.txt'));
        $this->assertEquals('second', $cache->read('test.txt'));
        $cache->flush();
        $this->assertEquals('second', $filesystem->read('test.txt'));
        $filesystem->delete('test.txt');
        $cache->flush();
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testVisibility($filesystem, $adapter, $cache)
    {
        $filesystem->write('test.txt', 'something', 'private');
        $this->assertEquals(AdapterInterface::VISIBILITY_PRIVATE, $filesystem->getVisibility('test.txt'));
        $this->assertEquals(AdapterInterface::VISIBILITY_PRIVATE, $cache->getVisibility('test.txt'));
        $filesystem->flushCache();
        $this->assertEquals(AdapterInterface::VISIBILITY_PRIVATE, $filesystem->getVisibility('test.txt'));
        $filesystem->setVisibility('test.txt', AdapterInterface::VISIBILITY_PUBLIC);
        $this->assertEquals(AdapterInterface::VISIBILITY_PUBLIC, $filesystem->getVisibility('test.txt'));
        $this->assertEquals(AdapterInterface::VISIBILITY_PUBLIC, $cache->getVisibility('test.txt'));
        $filesystem->flushCache();
        $this->assertEquals(AdapterInterface::VISIBILITY_PUBLIC, $filesystem->getVisibility('test.txt'));
        $filesystem->delete('test.txt');
        $cache->flush();
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testGetMetadata($filesystem)
    {
        $filesystem->write('test.txt', 'contents');
        $meta = $filesystem->getMetadata('test.txt');
        $this->assertInternalType('array', $meta);
        $filesystem->flushCache();
        $meta = $filesystem->getMetadata('test.txt');
        $this->assertInternalType('array', $meta);
        $this->assertArrayHasKey('filename', $meta);
        $this->assertArrayHasKey('dirname', $meta);
        $this->assertArrayHasKey('path', $meta);
        $this->assertArrayHasKey('type', $meta);
        $this->assertArrayHasKey('basename', $meta);
        $this->assertArrayHasKey('extension', $meta);
        $filesystem->delete('test.txt');
    }

    public function testRenameFailure()
    {
        $cache = new Cache\Memory(__DIR__);
        $this->assertFalse($cache->rename('something', 'to.this'));
    }

    public function testCacheStorage()
    {
        $cache = new Cache\Memory(__DIR__);
        $input = array(array('contents' => 'hehe', 'filename' => 'with contents'), array('filename' => 'no contents'));
        $expected = array(array('filename' => 'with contents'), array('filename' => 'no contents'));
        $json = json_encode(array(array(),array()));
        $output = $cache->cleanContents($input);
        $this->assertEquals($expected, $output);
        $this->assertEquals($json, $cache->getForStorage());
        $input = json_encode(array(array(),array()));
        $cache->setFromStorage($input);
        $this->assertEquals($input, $cache->getForStorage());
    }

    public function testAutosave()
    {
        $cache = new Cache\Memory(__DIR__);
        $this->assertTrue($cache->getAutosave());
        $cache->setAutosave(false);
        $this->assertFalse($cache->getAutosave());
    }

    public function failProvider()
    {
        return array(
            array('rename', true),
            array('write', false),
            array('update', true),
            array('read', true),
            array('delete', true),
            array('deleteDir', true),
            array('getMimetype', true),
            array('getTimestamp', true),
            array('getSize', true),
            array('getVisibility', true),
            array('setVisibility', true),
            array('getMetadata', true),
        );
    }

    /**
     * @dataProvider failProvider
     */
    public function testAdapterFail($method, $hasfile)
    {
        $mock = \Mockery::mock('League\Flysystem\Adapter\AbstractAdapter');
        $cachemock = \Mockery::mock('League\Flysystem\Cache\AbstractCache');
        $cachemock->shouldReceive('load')->andReturn(array());
        $cachemock->shouldReceive('has')->andReturn(null);
        $cachemock->shouldReceive('isComplete')->andReturn(false);
        $cachemock->shouldReceive('updateObject')->andReturn(false);
        $cachemock->shouldReceive('storeMiss')->andReturn(false);
        $mock->shouldReceive('__toString')->andReturn('Flysystem\Adapter\AbstractAdapter');
        $cachemock->shouldReceive('__toString')->andReturn('Flysystem\Cache\AbstractCache');
        $mock->shouldReceive('has')->with('other.txt')->andReturn(false);
        $cachemock->shouldReceive($method)->andReturn(false);
        $mock->shouldReceive('has')->with('dummy.txt')->andReturn($hasfile);
        $mock->shouldReceive($method)->andReturn(false);
        $filesystem = new Filesystem($mock, $cachemock);
        $this->assertFalse($filesystem->{$method}('dummy.txt', 'other.txt'));
    }

    public function testFailingPut()
    {
        $mock = \Mockery::mock('League\Flysystem\Adapter\AbstractAdapter');
        $cachemock = \Mockery::mock('League\Flysystem\Cache\AbstractCache');
        $cachemock->shouldReceive('load')->andReturn(array());
        $cachemock->shouldReceive('has')->andReturn(false);
        $cachemock->shouldReceive('isComplete')->andReturn(false);
        $cachemock->shouldReceive('updateObject')->andReturn(false);
        $mock->shouldReceive('__toString')->andReturn('Flysystem\Adapter\AbstractAdapter');
        $cachemock->shouldReceive('__toString')->andReturn('Flysystem\Cache\AbstractCache');

        $filesystem = new Filesystem($mock, $cachemock);
        $mock->shouldReceive('write')->andReturn(false);
        $mock->shouldReceive('update')->andReturn(false);

        $mock->shouldReceive('has')->with('dummy.txt')->andReturn(true);
        $this->assertFalse($filesystem->put('dummy.txt', 'content'));

        $mock->shouldReceive('has')->with('dummy2.txt')->andReturn(false);
        $this->assertFalse($filesystem->put('dummy2.txt', 'content'));
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testCreateDir($filesystem)
    {
        $filesystem->createDir('dirname');
        $this->assertTrue(is_dir(__DIR__.'/files/dirname'));
        $filesystem->deleteDir('dirname');
    }

    public function testNoop()
    {
        $filesystem = new Filesystem(new Adapter\Local(__DIR__.'/files'), new Cache\Noop);
        $filesystem->write('test.txt', 'contents');
        $this->assertTrue($filesystem->has('test.txt'));
        $this->assertInternalType('array', $filesystem->listContents());
        $this->assertInternalType('array', $filesystem->listContents('', true));
        $cache = $filesystem->getCache();
        $cache->setComplete('', false);
        $cache->flush();
        $cache->autosave();
        $this->assertFalse($cache->isComplete('', false));
        $this->assertFalse($cache->read('something'));
        $this->assertFalse($cache->getMetadata('something'));
        $this->assertFalse($cache->getMimetype('something'));
        $this->assertFalse($cache->getSize('something'));
        $this->assertFalse($cache->getTimestamp('something'));
        $this->assertFalse($cache->getVisibility('something'));
        $this->assertFalse($cache->listContents('', false));
        $filesystem->delete('test.txt');

        $this->assertEquals(array(), $cache->storeContents('unknwon', array(
            array('path' => 'some/file.txt'),
        ), false));
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testListPaths($filesystem)
    {
        if ( ! $filesystem->has('test.txt'))
            $filesystem->write('test.txt', 'something');
        $filesystem->flushCache();
        $listing = $filesystem->listPaths();
        $this->assertContainsOnly('string', $listing, true);
    }

    /**
     * @dataProvider filesystemProvider
     */
    public function testListWith($filesystem)
    {
        $filesystem->flushCache();

        if ( ! $filesystem->has('test.txt'))
            $filesystem->write('test.txt', 'something');

        $listing = $filesystem->listWith(array('mimetype'), '', true);
        $this->assertContainsOnly('array', $listing, true);
        $first = reset($listing);
        $this->assertArrayHasKey('mimetype', $first);
    }

    /**
     * @dataProvider  filesystemProvider
     * @expectedException  InvalidArgumentException
     */
    public function testListWithInvalid($filesystem)
    {
        $filesystem->flushCache();
        if ( ! $filesystem->has('test.txt'))
            $filesystem->write('test.txt', 'something');
        $listing = $filesystem->listWith(array('unknowntype'));
    }

    /**
     * @dataProvider  filesystemProvider
     */
    public function testGet($filesystem, $adapter, $cache)
    {
        if ( ! $filesystem->has('nested/file.txt')) {
            $filesystem->write('nested/file.txt', 'contents');
        }

        $handler = $filesystem->get('nested/file.txt');
        $this->assertInstanceOf('League\Flysystem\Handler', $handler);
        $this->assertInstanceOf('League\Flysystem\File', $handler);
        $this->assertEquals(8, $handler->getSize());
        $this->assertEquals('nested/file.txt', $handler->getPath());
        $this->assertEquals('text/plain', $handler->getMimetype());
        $this->assertEquals('file', $handler->getType());
        $this->assertTrue($handler->isFile());
        $this->assertFalse($handler->isDir());
        $this->assertInternalType('integer', $handler->getTimestamp());
        $this->assertEquals('contents', $handler->read());
        $handler->delete();
        $this->assertFalse($filesystem->has('nested/file.txt'));
        $handler = $filesystem->get('nested');
        $this->assertTrue($handler->isDir());
        $this->assertCount(0, $handler->getContents(true));
        $filesystem->write('nested/other.txt', 'contents');
        $this->assertCount(1, $handler->getContents(true));
        $handler->delete();
        $this->assertFalse($filesystem->has('nested'));

        $cache->flush();
        $filesystem->write('deeply/nested/thing.txt', 'contents');
        $filesystem->write('other/nested/thing.txt', 'contents');
        // var_dump($filesystem->listContents('deeply'));
        $this->assertCount(1, $filesystem->listContents('deeply'));
        $this->assertCount(2, $filesystem->listContents('deeply', true));
        $this->assertCount(2, $filesystem->listContents('deeply', true));
        $this->assertCount(1, $cache->listContents('deeply'));
        $this->assertCount(2, $cache->listContents('deeply', true));
    }

    public function testAbstractReadStream()
    {
        $mock = \Mockery::mock('League\Flysystem\Adapter\AbstractAdapter[read,write,update,getTimestamp,getMetadata,getMimetype,getSize,delete,deleteDir,listContents,has,createDir,rename]');
        $mock->shouldReceive('read')->twice()->andReturn(false, array('contents' => 'something'));
        $this->assertFalse($mock->readStream('path'));
        $data = $mock->readStream('path');
        $this->assertInternalType('resource', $data['stream']);
    }
}
