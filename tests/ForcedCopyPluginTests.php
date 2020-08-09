<?php

use League\Flysystem\Plugin\ForcedCopy;
use PHPUnit\Framework\TestCase;

class ForcedCopyPluginTests extends TestCase
{

    protected $filesystem;
    protected $plugin;

    public function setUp(): void
    {
        $this->filesystem = $this->prophesize('League\Flysystem\FilesystemInterface');
        $this->plugin = new ForcedCopy();
        $this->plugin->setFilesystem($this->filesystem->reveal());
    }

    public function testPluginSuccess()
    {
        $this->assertSame('forceCopy', $this->plugin->getMethod());

        $this->filesystem->delete('newpath')->willReturn(true)->shouldBeCalled();
        $this->filesystem->copy('path', 'newpath')->willReturn(true)->shouldBeCalled();

        $this->assertTrue($this->plugin->handle('path', 'newpath'));
    }

    public function testPluginDeleteNotExists()
    {
        $this->filesystem->delete('newpath')
            ->willThrow('League\Flysystem\FileNotFoundException', 'newpath')
            ->shouldBeCalled();

        $this->filesystem->copy('path', 'newpath')->willReturn(true)->shouldBeCalled();

        $this->assertTrue($this->plugin->handle('path', 'newpath'));
    }

    public function testPluginDeleteFail()
    {
        $this->filesystem->delete('newpath')->willReturn(false)->shouldBeCalled();
        $this->assertFalse($this->plugin->handle('path', 'newpath'));
    }
}
