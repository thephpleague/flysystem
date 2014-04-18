<?php

use League\Flysystem;

use League\Flysystem\Cache\Memory;

class AbstractCacheTest extends PHPUnit_Framework_TestCase
{
    public function testRecursiveResultWithNonRecursiveRequest()
    {
        $cache = new Memory;
        $input = array(
            array('path' => 'wanted/path.txt'),
            array('path' => 'unwanted/path.txt'),
        );
        $expected = array(
            array('dirname' => 'wanted', 'path' => 'wanted/path.txt', 'basename' => 'path.txt', 'filename' => 'path', 'extension' => 'txt'),
        );
        $output = $cache->storeContents('wanted', $input, false);
        $this->assertEquals($expected, $output);
    }

    public function testCopyFail()
    {
        $cache = new Memory;
        $this->assertFalse($cache->copy('one', 'two'));
    }
}
