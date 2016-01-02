<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Util\StreamHasher;

class StreamHasherTest extends \PHPUnit_Framework_TestCase
{
    public function testHasher()
    {
        $filename = __DIR__.'/../src/Filesystem.php';
        $this->assertEquals(
            md5_file($filename),
            (new StreamHasher('md5'))->hash(fopen($filename, 'r'))
        );
    }
}
