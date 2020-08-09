<?php


use League\Flysystem\Plugin\EmptyDir;
use PHPUnit\Framework\TestCase;

class EmptyDirPluginTests extends TestCase
{

    public function testPlugin()
    {
        $filesystem = $this->prophesize('League\Flysystem\FilesystemInterface');
        $plugin = new EmptyDir();
        $this->assertEquals('emptyDir', $plugin->getMethod());
        $plugin->setFilesystem($filesystem->reveal());
        $filesystem->listContents('dirname', false)->willReturn([
           ['type' => 'dir', 'path' => 'dirname/dir'],
           ['type' => 'file', 'path' => 'dirname/file.txt'],
           ['type' => 'dir', 'path' => 'dirname/another_dir'],
           ['type' => 'file', 'path' => 'dirname/another_file.txt'],
        ])->shouldBeCalled();

        $filesystem->delete('dirname/file.txt')->shouldBeCalled();
        $filesystem->delete('dirname/another_file.txt')->shouldBeCalled();
        $filesystem->deleteDir('dirname/dir')->shouldBeCalled();
        $filesystem->deleteDir('dirname/another_dir')->shouldBeCalled();

        $plugin->handle('dirname');
    }
}
