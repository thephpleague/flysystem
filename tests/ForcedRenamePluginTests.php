<?php


use League\Flysystem\Plugin\ForcedRename;

class ForcedRenamePluginTests extends PHPUnit_Framework_TestCase
{
    protected $filesystem;
    protected $plugin;

    public function setUp()
    {
        $this->filesystem = Mockery::mock('League\Flysystem\FilesystemInterface');
        $this->plugin = new ForcedRename();
        $this->plugin->setFilesystem($this->filesystem);
    }

    public function testPluginSuccess()
    {
        $this->assertSame('forceRename', $this->plugin->getMethod());

        $this->filesystem->shouldReceive('delete')->with('newpath')->andReturn(true);
        $this->filesystem->shouldReceive('rename')->with('path', 'newpath')->andReturn(true);

        $this->assertTrue($this->plugin->handle('path', 'newpath'));
    }

    public function testPluginDeleteNotExists()
    {
        $this->filesystem->shouldReceive('delete')
            ->with('newpath')
            ->andThrow('League\Flysystem\FileNotFoundException', 'newpath');

        $this->filesystem->shouldReceive('rename')->with('path', 'newpath')->andReturn(true);

        $this->assertTrue($this->plugin->handle('path', 'newpath'));
    }

    public function testPluginDeleteFail()
    {
        $this->filesystem->shouldReceive('delete')->with('newpath')->andReturn(false);
        $this->assertFalse($this->plugin->handle('path', 'newpath'));
    }
}
