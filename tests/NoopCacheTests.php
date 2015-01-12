<?php

use League\Flysystem\Cache\Noop;

class NoopCacheTests extends PHPUnit_Framework_TestCase
{
    public function testNoop()
    {
        $cache = new Noop();
        $this->assertEquals($cache, $cache->storeMiss('file.txt'));
        $this->assertNull($cache->setComplete('', false));
        $this->assertNull($cache->load());
        $this->assertNull($cache->flush());
        $this->assertNull($cache->has('path.txt'));
        $this->assertNull($cache->autosave());
        $this->assertFalse($cache->isComplete('', false));
        $this->assertFalse($cache->read('something'));
        $this->assertFalse($cache->readStream('something'));
        $this->assertFalse($cache->getMetadata('something'));
        $this->assertFalse($cache->getMimetype('something'));
        $this->assertFalse($cache->getSize('something'));
        $this->assertFalse($cache->getTimestamp('something'));
        $this->assertFalse($cache->getVisibility('something'));
        $this->assertEmpty($cache->listContents('', false));
        $this->assertFalse($cache->rename('', ''));
        $this->assertFalse($cache->copy('', ''));
        $this->assertNull($cache->save());
        $object = ['path' => 'path.ext'];
        $this->assertEquals($object, $cache->updateObject('path.txt', $object));
        $this->assertEquals([['path' => 'some/file.txt']], $cache->storeContents('unknwon', [
            ['path' => 'some/file.txt'],
        ], false));
    }
}