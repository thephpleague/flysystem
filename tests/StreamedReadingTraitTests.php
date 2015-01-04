<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Stub\StreamedReadingStub;

class StreamedReadingTraitTests extends \PHPUnit_Framework_TestCase
{
    public function testStreamRead()
    {
        $stub = new StreamedReadingStub();
        $result = $stub->readStream($input = 'true.ext');
        $this->assertInternalType('resource', $result['stream']);
        $this->assertEquals($input, stream_get_contents($result['stream']));
        fclose($result['stream']);
    }

    public function testStreamReadFail()
    {
        $stub = new StreamedReadingStub();
        $result = $stub->readStream('other.ext');
        $this->assertFalse($result);
    }
}
