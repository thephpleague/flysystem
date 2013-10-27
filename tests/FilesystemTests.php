<?php

namespace Flysystem;

class FlysystemTests extends \PHPUnit_Framework_TestCase
{
	public function setup()
	{
		$fs = new Adapter\Local(__DIR__.'/files');
		foreach (array_reverse($fs->listContents()) as $info) {
			if (is_file(__DIR__.'/files/'.$info['path'])) {
				unlink(__DIR__.'/files/'.$info['path']);
			} else {
				rmdir(__DIR__.'/files/'.$info['path']);
			}
		}
	}

	public function testInstantiable()
	{
		$instance = new Filesystem($adapter = new Adapter\Local(__DIR__.'files'), $cache = new Cache\Memory);
	}

	public function filesystemProvider()
	{
		$adapter = new Adapter\Local(__DIR__.'/files');
		$cache = new Cache\Memory;
		$filesystem = new Filesystem($adapter, $cache);

		return [
			[$filesystem, $adapter, $cache]
		];
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
		$this->assertFalse($cache->isComplete());
		$filesystem->listContents();
		$this->assertTrue($cache->isComplete());
		$cache->flush();
		$this->assertFalse($cache->isComplete());
	}

	/**
	 * @dataProvider filesystemProvider
	 */
	public function testDepGetters($filesystem)
	{
		$this->assertInstanceOf('Flysystem\CacheInterface', $filesystem->getCache());
		$this->assertInstanceOf('Flysystem\ReadInterface', $filesystem->getCache());
		$this->assertInstanceOf('Flysystem\Cache\AbstractCache', $filesystem->getCache());
		$this->assertInstanceOf('Flysystem\AdapterInterface', $filesystem->getAdapter());
		$this->assertInstanceOf('Flysystem\ReadInterface', $filesystem->getAdapter());
		$this->assertInstanceOf('Flysystem\Adapter\AbstractAdapter', $filesystem->getAdapter());
	}

	/**
	 * @dataProvider filesystemProvider
	 */
	public function testWrite($filesystem, $adapter, $cache)
	{
		$this->assertEquals(12, $filesystem->write('some_file.txt', 'some content'));
		$this->assertTrue($filesystem->has('some_file.txt'));
		$this->assertTrue($cache->has('some_file.txt'));
		$this->assertTrue($adapter->has('some_file.txt'));
		$this->assertCount(1, $filesystem->listContents());
		$this->assertCount(1, $cache->listContents());
		$this->assertCount(1, $adapter->listContents());

		$filesystem->rename('some_file.txt', 'other_name.txt');
		$this->assertFalse($filesystem->has('some_file.txt'));
		$this->assertFalse($cache->has('some_file.txt'));
		$this->assertFalse($adapter->has('some_file.txt'));
		$this->assertTrue($filesystem->has('other_name.txt'));
		$this->assertTrue($cache->has('other_name.txt'));
		$this->assertTrue($adapter->has('other_name.txt'));
		$this->assertCount(1, $filesystem->listContents());
		$this->assertCount(1, $cache->listContents());
		$this->assertCount(1, $adapter->listContents());

		$filesystem->delete('other_name.txt');
		$this->assertFalse($filesystem->has('other_name.txt'));
		$this->assertFalse($cache->has('other_name.txt'));
		$this->assertFalse($adapter->has('other_name.txt'));
		$this->assertCount(0, $filesystem->listContents());
		$this->assertCount(0, $cache->listContents());
		$this->assertCount(0, $adapter->listContents());
	}

	/**
	 * @dataProvider filesystemProvider
	 * @expectedException  Flysystem\FileExistsException
	 */
	public function testFileExists($filesystem)
	{
		$filesystem->write('../FilesystemTests.php', 'something');
	}

	/**
	 * @dataProvider filesystemProvider
	 * @expectedException  Flysystem\FileNotFoundException
	 */
	public function testFileNotFoundUpdate($filesystem)
	{
		$filesystem->update('not_found', 'content');
	}

	/**
	 * @dataProvider filesystemProvider
	 * @expectedException  Flysystem\FileNotFoundException
	 */
	public function testFileNotFoundDelete($filesystem)
	{
		$filesystem->delete('not_found');
	}

	/**
	 * @dataProvider filesystemProvider
	 */
	public function testImplicidDirs($filesystem)
	{
		$this->assertCount(0, $filesystem->listContents());
		$filesystem->write('dummy.txt', 'content');
		$this->assertCount(1, $filesystem->listContents());
		$filesystem->write('nested/dir/dummy.txt', 'text');
		$this->assertCount(4, $filesystem->listContents());
		$filesystem->deleteDir('nested');
		$this->assertCount(1, $filesystem->listContents());
		$filesystem->delete('dummy.txt');
		$this->assertCount(0, $filesystem->listContents());
		$filesystem->flushCache();
	}

	/**
	 * @dataProvider filesystemProvider
	 */
	public function testGetMimetype($filesystem, $adapter, $cache)
	{
		$filesystem->write('this.txt', 'something');
		$this->assertEquals('text/plain', $filesystem->getMimetype('this.txt'));
		$cache->updateObject('this.txt', ['mimetype' => 'injected']);
		$this->assertEquals('injected', $filesystem->getMimetype('this.txt'));
		$cache->flush();
		$this->assertEquals('text/plain', $filesystem->getMimetype('this.txt'));
		$filesystem->delete('this.txt');
	}
}