<?php

namespace Flysystem;

class FlysystemTests extends \PHPUnit_Framework_TestCase
{
	public function testInstantiable()
	{
		$instance = new Filesystem($adapter = new Adapter\Local(__DIR__.'../resources'), $cache = new Cache\Memory);
	}

	public function filesystemProvider()
	{
		$adapter = new Adapter\Local(__DIR__.'/../resources/');
		$filesystem = new Filesystem($adapter, $cache);

		return [
			[$filesystem]
		];
	}

	/**
	 * @dataProvider filesystemProvider
	 */
	public function testListContents($filesystem)
	{
		$result = $filesystem->listContents();
		$this->assertInternalType('array', $result);
		$this->assertCount(4, $result);
	}
}