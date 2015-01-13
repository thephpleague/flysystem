<?php

use League\Flysystem\Cache\Memory;
use League\Flysystem\Util;

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

    public function testGetMetadataFail()
    {
        $cache = new Memory();
        $this->assertFalse($cache->getMetadata('path.txt'));
    }

    public function metaGetterProvider()
    {
        return [
            ['getTimestamp', 'timestamp', 12344],
            ['getMimetype', 'mimetype', 'text/plain'],
            ['getSize', 'size', 12],
            ['getVisibility', 'visibility', 'private'],
            ['read', 'contents', '__contents__'],
        ];
    }

    /**
     * @dataProvider metaGetterProvider
     * @param $method
     * @param $key
     * @param $value
     */
    public function testMetaGetters($method, $key, $value)
    {
        $cache = new Memory();
        $this->assertFalse($cache->{$method}('path.txt'));
        $cache->updateObject('path.txt', $object = [
            'path' => 'path.txt',
            'type' => 'file',
            $key => $value,
        ] + Util::pathinfo('path.txt'), true);
        $this->assertEquals($object, $cache->{$method}('path.txt'));
        $this->assertEquals($object, $cache->getMetadata('path.txt'));
    }

    public function testGetDerivedMimetype()
    {
        $cache = new Memory();
        $cache->updateObject('path.txt', [
            'contents' => 'something',
        ]);
        $response = $cache->getMimetype('path.txt');
        $this->assertEquals('text/plain', $response['mimetype']);
    }

    public function testCopyFail()
    {
        $cache = new Memory();
        $cache->copy('one', 'two');
        $this->assertNull($cache->has('two'));
        $this->assertNull($cache->load());
    }

    public function testStoreContents()
    {
        $cache = new Memory();
        $cache->storeContents('dirname', [
            ['path' => 'dirname', 'type' => 'dir'],
            ['path' => 'dirname/nested', 'type' => 'dir'],
            ['path' => 'dirname/nested/deep', 'type' => 'dir'],
            ['path' => 'other/nested/deep', 'type' => 'dir'],
        ], true);

        $this->isTrue($cache->isComplete('other/nested', true));
    }

    public function testDelete()
    {
        $cache = new Memory();
        $cache->updateObject('path.txt', ['type' => 'file']);
        $this->assertTrue($cache->has('path.txt'));
        $cache->delete('path.txt');
        $this->assertFalse($cache->has('path.txt'));
    }

    public function testDeleteDir()
    {
        $cache = new Memory();
        $cache->storeContents('dirname', [
            ['path' => 'dirname/path.txt', 'type' => 'file']
        ]);
        $this->assertTrue($cache->isComplete('dirname', false));
        $this->assertTrue($cache->has('dirname/path.txt'));
        $cache->deleteDir('dirname');
        $this->assertFalse($cache->isComplete('dirname', false));
        $this->assertNull($cache->has('dirname/path.txt'));
    }

    public function testReadStream()
    {
        $cache = new Memory();
        $this->assertFalse($cache->readStream('path.txt'));
    }

    public function testRename()
    {
        $cache = new Memory();
        $cache->updateObject('path.txt', ['type' => 'file']);
        $cache->rename('path.txt', 'newpath.txt');
        $this->assertTrue($cache->has('newpath.txt'));
    }

    public function testCopy()
    {
        $cache = new Memory();
        $cache->updateObject('path.txt', ['type' => 'file']);
        $cache->copy('path.txt', 'newpath.txt');
        $this->assertTrue($cache->has('newpath.txt'));
    }

    public function testComplextListContents()
    {
        $cache = new Memory();
        $cache->storeContents('', [
            ['path' => 'dirname', 'type' => 'dir'],
            ['path' => 'dirname/file.txt', 'type' => 'file'],
            ['path' => 'other', 'type' => 'dir'],
            ['path' => 'other/file.txt', 'type' => 'file'],
            ['path' => 'other/nested/file.txt', 'type' => 'file'],
        ]);

        $this->assertCount(3, $cache->listContents('other', true));
    }
}