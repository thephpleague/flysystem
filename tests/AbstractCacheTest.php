<?php

use League\Flysystem;

class AbstractCacheTest extends PHPUnit_Framework_TestCase
{
    public function testRecursiveResultWithNonRecursiveRequest()
    {
        $cache = new League\Flysystem\Cache\Memory;
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
}
