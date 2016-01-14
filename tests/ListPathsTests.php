<?php


use League\Flysystem\Plugin\ListPaths;

class ListPathsTests extends \PHPUnit_Framework_TestCase
{
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
        $plugin = new ListPaths();
        $this->assertEquals('listPaths', $plugin->getMethod());
        $this->filesystem->listContents('dirname', true)->willReturn([
            ['path' => 'dirname/path.txt'],
        ]);
        $plugin->setFilesystem($this->actualFilesystem);
        $output = $plugin->handle('dirname', true);
        $this->assertEquals(['dirname/path.txt'], $output);
    }
}
