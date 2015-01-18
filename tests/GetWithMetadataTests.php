<?php


use League\Flysystem\Plugin\GetWithMetadata;
use Prophecy\PhpUnit\ProphecyTestCase;
use Prophecy\Prophecy\ObjectProphecy;

class GetWithMetadataTests extends ProphecyTestCase
{
    /**
     * @var ObjectProphecy
     */
    private $prophecy;

    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /**
     * @before
     */
    public function setupFilesystem()
    {
        $this->prophecy = $this->prophesize('League\Flysystem\FilesystemInterface');
        $this->filesystem = $this->prophecy->reveal();
    }

    public function testGetMethod()
    {
        $plugin = new GetWithMetadata();
        $this->assertEquals('getWithMetadata', $plugin->getMethod());
    }

    public function testHandle()
    {
        $this->prophecy->getMetadata('path.txt')->willReturn([
            'path' => 'path.txt',
            'type' => 'file',
        ]);
        $this->prophecy->getMimetype('path.txt')->willReturn('text/plain');

        $plugin = new GetWithMetadata();
        $plugin->setFilesystem($this->filesystem);
        $output = $plugin->handle('path.txt', ['mimetype']);
        $this->assertEquals([
            'path' => 'path.txt',
            'type' => 'file',
            'mimetype' => 'text/plain',
        ], $output);
    }

    public function testHandleFail()
    {
        $this->prophecy->getMetadata('path.txt')->willReturn(false);
        $plugin = new GetWithMetadata();
        $plugin->setFilesystem($this->filesystem);
        $output = $plugin->handle('path.txt', ['mimetype']);
        $this->assertFalse($output);
    }

    public function testHandleInvalid()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->prophecy->getMetadata('path.txt')->willReturn([
            'path' => 'path.txt',
            'type' => 'file',
        ]);

        $plugin = new GetWithMetadata();
        $plugin->setFilesystem($this->filesystem);
        $output = $plugin->handle('path.txt', ['invalid']);
    }
}
