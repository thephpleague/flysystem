<?php

use League\Flysystem\Plugin\ListDirectories;
use PHPUnit\Framework\TestCase;

class ListDirectoriesTests extends TestCase
{
    use \PHPUnitHacks;

    private $filesystem;
    private $actualFilesystem;

    /**
     * @before
     */
    public function setupFilesystem()
    {
        $this->filesystem = $this->prophesize('League\Flysystem\FilesystemInterface');
        $this->actualFilesystem = $this->filesystem->reveal();
    }

    public function testHandle()
    {
        $plugin = new ListDirectories();
        $this->assertEquals('listDirectories', $plugin->getMethod());
        $this->filesystem->listContents('dirname', true)->willReturn([
            ['path' => 'dirname', 'type' => 'dir'],
            ['path' => 'dirname/path.txt', 'type' => 'file'],
            ['path' => 'dirname/foo', 'type' => 'dir'],
        ]);
        $plugin->setFilesystem($this->actualFilesystem);
        $output = $plugin->handle('dirname', true);
        $this->assertEquals([['path' => 'dirname', 'type' => 'dir'], ['path' => 'dirname/foo', 'type' => 'dir']], $output);
    }
}
