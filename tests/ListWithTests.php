<?php

namespace League\Flysystem\Adapter;

use InvalidArgumentException;
use League\Flysystem\Filesystem;
use League\Flysystem\Plugin\ListWith;
use PHPUnit\Framework\TestCase;

class ListWithTests extends TestCase
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem(new Local(__DIR__.'/../test_files/list_with'));
    }

    protected function tearDown(): void
    {
        $fs = new Filesystem(new Local(__DIR__.'/../test_files/'));
        $fs->deleteDir('list_with');
    }

    public function testHandle()
    {
        $this->filesystem->write('path.txt', 'contents');
        $plugin = new ListWith();
        $plugin->setFilesystem($this->filesystem);
        $this->assertEquals('listWith', $plugin->getMethod());
        $listing = $plugin->handle(['mimetype'], '', true);
        $this->assertContainsOnly('array', $listing, true);
        $first = reset($listing);
        $this->assertArrayHasKey('mimetype', $first);
    }

    public function testInvalidInput()
    {
        $this->filesystem->write('path.txt', 'contents');
        $this->expectException(InvalidArgumentException::class);
        $plugin = new ListWith();
        $plugin->setFilesystem($this->filesystem);
        $plugin->handle(['invalid'], '', true);
    }
}
