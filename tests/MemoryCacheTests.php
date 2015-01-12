<?php

use League\Flysystem\Cache\Memory;

class MemoryCacheTests extends PHPUnit_Framework_TestCase
{
    public function testAutosave()
    {
        $cache = new Memory();
        $cache->setAutosave(true);
        $this->assertTrue($cache->getAutosave());
        $cache->setAutosave(false);
        $this->assertFalse($cache->getAutosave());
    }

    public function testCacheMiss()
    {
        $cache = new Memory();
        $cache->storeMiss('path.txt');
        $this->assertFalse($cache->has('path.txt'));
    }

    public function testIsComplete()
    {
        $cache = new Memory();
        $this->assertFalse($cache->isComplete('dirname', false));
        $cache->setComplete('dirname', false);
        $this->assertFalse($cache->isComplete('dirname', true));
        $cache->setComplete('dirname', true);
        $this->assertTrue($cache->isComplete('dirname', true));
    }

    public function testCleanContents()
    {
        $cache = new Memory();
        $input = [[
            'path'       => 'path.txt',
            'visibility' => 'public',
            'invalid'    => 'thing',
        ]];

        $expected = [[
            'path'       => 'path.txt',
            'visibility' => 'public',
        ]];

        $output = $cache->cleanContents($input);
        $this->assertEquals($expected, $output);

    }

    public function testGetForStorage()
    {
        $cache = new Memory();
        $input = [[
            'path' => 'path.txt',
            'visibility' => 'public',
            'type' => 'file',
        ]];

        $cache->storeContents('', $input, true);
        $contents = $cache->listContents('', true);
        $cached = [];
        foreach ($contents as $item) {
            $cached[$item['path']] = $item;
        }

        $this->assertEquals(json_encode([$cached, ['' => 'recursive']]), $cache->getForStorage());
    }

    public function testParentCompleteIsUsedDuringHas()
    {
        $cache = new Memory();
        $cache->setComplete('dirname', false);
        $this->assertFalse($cache->has('dirname/path.txt'));
    }

    public function testFlush()
    {
        $cache = new Memory();
        $cache->setComplete('dirname', true);
        $cache->updateObject('path.txt', [
            'path' => 'path.txt',
            'visibility' => 'public',
        ]);
        $cache->flush();
        $this->assertFalse($cache->isComplete('dirname', true));
        $this->assertNull($cache->has('path.txt'));
    }

    public function testSetFromStorage()
    {
        $cache = new Memory();
        $json = [[
            'path.txt' => ['path' => 'path.txt', 'type' => 'file'],
        ], ['dirname' => 'recursive']];
        $jsonString = json_encode($json);
        $cache->setFromStorage($jsonString);
        $this->assertTrue($cache->has('path.txt'));
        $this->assertTrue($cache->isComplete('dirname', true));
    }


}