<?php

use League\Flysystem\Cache\Memory;

class AbstractCacheTest extends PHPUnit_Framework_TestCase
{
    public function testRecursiveResultWithNonRecursiveRequest()
    {
        $cache = new Memory();
        $input = [
            ['path' => 'this_is/unwanted/path.txt'],
            ['path' => 'this_is/wanted/not/path.txt'],
            ['path' => 'this_is/wanted/path.txt'],
        ];
        $expected = [
            ['dirname' => 'this_is/wanted', 'path' => 'this_is/wanted/path.txt', 'basename' => 'path.txt', 'filename' => 'path', 'extension' => 'txt'],
        ];
        $output = $cache->storeContents('this_is/wanted', $input, false);

        ksort($output);
        ksort($expected);

        $this->assertEquals($expected, $output);
    }

    public function testCopyFail()
    {
        $cache = new Memory();
        $this->assertFalse($cache->copy('one', 'two'));
    }

    public function testValidResource()
    {
        $stream = tmpfile();
        $cache = new Memory();
        $cache->updateObject('path.txt', ['stream' => $stream]);
        $this->assertInternalType('resource', $cache->readStream('path.txt'));
        fclose($stream);
        $this->assertFalse($cache->readStream('path.txt'));
    }
}
