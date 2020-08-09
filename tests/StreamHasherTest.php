<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Util\StreamHasher;
use PHPUnit\Framework\TestCase;

class StreamHasherTest extends TestCase
{

    public function testHasher()
    {
        $filename = __DIR__ . '/../src/Filesystem.php';
        $this->assertEquals(
            md5_file($filename),
            (new StreamHasher('md5'))->hash(fopen($filename, 'r'))
        );
    }
}
