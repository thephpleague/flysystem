<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Plugin\ListWith;
use Prophecy\PhpUnit\ProphecyTestCase;

class ListWithTests extends ProphecyTestCase
{
    public function testHandle()
    {
        $prophecy = $this->prophesize('League\Flysystem\Filesystem');
        $prophecy->listContents('', true)->willReturn([
           ['path' => 'path.txt', 'type' => 'file'],
        ]);
        $prophecy->getMimetype('path.txt')->willReturn('text/plain');
        $filesystem = $prophecy->reveal();

        $plugin = new ListWith();
        $plugin->setFilesystem($filesystem);
        $this->assertEquals('listWith', $plugin->getMethod());
        $listing = $plugin->handle(['mimetype'], '', true);
        $this->assertContainsOnly('array', $listing, true);
        $first = reset($listing);
        $this->assertArrayHasKey('mimetype', $first);
    }

    public function testInvalidInput()
    {
        $prophecy = $this->prophesize('League\Flysystem\Filesystem');
        $prophecy->listContents('', true)->willReturn([
            ['path' => 'path.txt', 'type' => 'file'],
        ]);
        $filesystem = $prophecy->reveal();

        $this->setExpectedException('InvalidArgumentException');
        $plugin = new ListWith();
        $plugin->setFilesystem($filesystem);
        $plugin->handle(['invalid'], '', true);
    }
}
