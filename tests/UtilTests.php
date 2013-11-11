<?php

namespace Flysystem;

class UtilTests extends \PHPUnit_Framework_TestCase
{
	public function testEmulateDirectories()
	{
		$input = array(array('dirname' => '', 'filename' => 'dummy'), array('dirname' => 'something', 'filename' => 'dummy'));
		$output = Util::emulateDirectories($input);
		$this->assertCount(3, $output);
	}


	public function testContentSize()
	{
		$this->assertEquals(5, Util::contentSize('12345'));
		$this->assertEquals(3, Util::contentSize('135'));
	}

	public function mapProvider()
	{
		return array(
			array(array('from.this' => 'value'), array('from.this' => 'to.this'), array('to.this' => 'value')),
			array(array('from.this' => 'value', 'no.mapping' => 'lost'), array('from.this' => 'to.this'), array('to.this' => 'value')),
		);
	}

	/**
	 * @dataProvider  mapProvider
	 */
	public function testMap($from, $map, $expected)
	{
		$result = Util::map($from, $map);
		$this->assertEquals($expected, $result);
	}


	public function dirnameProvider()
	{
		return array(
			array('filename.txt', ''),
			array('dirname/filename.txt', 'dirname'),
			array('dirname/subdir', 'dirname'),
		);
	}

	/**
	 * @dataProvider  dirnameProvider
	 */
	public function testDirname($input, $expected)
	{
		$result = Util::dirname($input);
		$this->assertEquals($expected, $result);
	}
}