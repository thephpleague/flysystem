<?php

use League\Flysystem;

use League\Flysystem\Cache\Memory;

class AbstractCacheTest extends PHPUnit_Framework_TestCase
{
    public function testRecursiveResultWithNonRecursiveRequest()
    {
        $cache = new Memory;
        $input = array(
            array('path' => 'this_is/unwanted/path.txt'),
            array('path' => 'this_is/wanted/not/path.txt'),
            array('path' => 'this_is/wanted/path.txt'),
        );
        $expected = array(
            array('dirname' => 'this_is/wanted', 'path' => 'this_is/wanted/path.txt', 'basename' => 'path.txt', 'filename' => 'path', 'extension' => 'txt'),
        );
        $output = $cache->storeContents('this_is/wanted', $input, false);

        ksort($output);
        ksort($expected);

        $this->assertEquals($expected, $output);
    }

    public function testCopyFail()
    {
        $cache = new Memory;
        $this->assertFalse($cache->copy('one', 'two'));
    }
}
