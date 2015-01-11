<?php


use League\Flysystem\Plugin\EmptyDir;

class EmptyDirPluginTests extends PHPUnit_Framework_TestCase
{
    public function testPlugin()
    {
        $filesystem = Mockery::mock('League\\Flysystem\\FilesystemInterface');
        $plugin = new EmptyDir();
        $this->assertEquals('emptyDir', $plugin->getMethod());
        $plugin->setFilesystem($filesystem);
        $filesystem->shouldReceive('listContents')->with('dirname', false)->andReturn([
           ['type' => 'dir', 'path' => 'dirname/dir'],
           ['type' => 'file', 'path' => 'dirname/file.txt'],
           ['type' => 'dir', 'path' => 'dirname/another_dir'],
           ['type' => 'file', 'path' => 'dirname/another_file.txt'],
        ]);

        $filesystem->shouldReceive('delete')->with('dirname/file.txt');
        $filesystem->shouldReceive('delete')->with('dirname/another_file.txt');
        $filesystem->shouldReceive('deleteDir')->with('dirname/dir');
        $filesystem->shouldReceive('deleteDir')->with('dirname/another_dir');

        $plugin->handle('dirname');
    }
}
