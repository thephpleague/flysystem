<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Stub\StreamedReadingStub;
use PHPUnit\Framework\TestCase;

class StreamedReadingTraitTests extends TestCase
{

    public function testStreamRead()
    {
        $stub = new StreamedReadingStub();
        $result = $stub->readStream($input = 'true.ext');
        $this->assertIsResource($result['stream']);
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
