<?php

namespace Flysystem;

class UtilTests extends \PHPUnit_Framework_TestCase
{
	public function testEmulateDirectories()
	{
		$input = [['dirname' => '', 'filename' => 'dummy'], ['dirname' => 'something', 'filename' => 'dummy']];
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
		return [
			[['from.this' => 'value'], ['from.this' => 'to.this'], ['to.this' => 'value']],
			[['from.this' => 'value', 'no.mapping' => 'lost'], ['from.this' => 'to.this'], ['to.this' => 'value']],
		];
	}

	/**
	 * @dataProvider  mapProvider
	 */
	public function testMap($from, $map, $expected)
	{
		$result = Util::map($from, $map);
		$this->assertEquals($expected, $result);
	}
}