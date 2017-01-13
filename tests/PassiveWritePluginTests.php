<?php

use League\Flysystem\Plugin\PassiveWrite;

class PassiveWritePluginTests extends PHPUnit_Framework_TestCase
{
    protected $filesystem;
    protected $plugin;

    public function setUp()
    {
        $this->filesystem = Mockery::mock('League\Flysystem\FilesystemInterface');
        $this->plugin = new PassiveWrite();
        $this->plugin->setFilesystem($this->filesystem);
    }

    public function testPluginSuccess()
    {
        $this->assertSame('passiveWrite', $this->plugin->getMethod());
        $this->filesystem->shouldReceive('write')->with('path', 'contents')->andReturn(true);
        $this->assertTrue($this->plugin->handle('path', 'contents'));
    }

    public function testPluginFileExists()
    {
        $this->filesystem->shouldReceive('write')
            ->with('path', 'contents')
            ->andThrow('League\Flysystem\FileExistsException', 'path');
        $this->assertTrue($this->plugin->handle('path', 'newpath'));
    }

    public function testPluginFail()
    {
        $this->filesystem->shouldReceive('write')->with('path', 'contents')->andReturn(false);
        $this->assertFalse($this->plugin->handle('path', 'contents'));
    }
}
