<?php

use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\Plugin\ListPaths;
use PHPUnit\Framework\TestCase;

abstract class FtpIntegrationTestCase extends TestCase
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @test
     */
    public function testInstantiable()
    {
        if ( ! defined('FTP_BINARY')) {
            $this->markTestSkipped('The FTP_BINARY constant is not defined');
        }
    }

    /**
     * @return AdapterInterface
     */
    abstract protected function setup_adapter();

    /**
     * @before
     */
    public function setup_filesystem()
    {
        if ( ! defined('FTP_BINARY')) {
            return;
        }
        $adapter = $this->setup_adapter();
        $this->filesystem = new Filesystem($adapter);
        $this->filesystem->addPlugin(new ListPaths());

        foreach ($this->filesystem->listContents('', true) as $item) {
            if ($item['path'] == '') {
                continue;
            }
            if ($item['type'] == 'dir') {
                $this->filesystem->deleteDir($item['path']);
            } else {
                $this->filesystem->delete($item['path']);
            }
        }
    }

    /**
     * @test
     * @depends testInstantiable
     */
    public function writing_reading_deleting()
    {
        $filesystem = $this->filesystem;
        $this->assertTrue($filesystem->put('path.txt', 'file contents'));
        $this->assertEquals('file contents', $filesystem->read('path.txt'));
        $this->assertTrue($filesystem->delete('path.txt'));
    }

    /**
     * @test
     * @depends testInstantiable
     */
    public function writing_in_a_directory_and_deleting_the_directory()
    {
        $filesystem = $this->filesystem;
        $this->assertTrue($filesystem->write('deeply/nested/path.txt', 'contents'));
        $this->assertTrue($filesystem->has('deeply/nested'));
        $this->assertTrue($filesystem->has('deeply'));
        $this->assertTrue($filesystem->has('deeply/nested/path.txt'));
        $this->assertTrue($filesystem->deleteDir('deeply/nested'));
        $this->assertFalse($filesystem->has('deeply/nested'));
        $this->assertFalse($filesystem->has('deeply/nested/path.txt'));
        $this->assertTrue($filesystem->has('deeply'));
        $this->assertTrue($filesystem->deleteDir('deeply'));
        $this->assertFalse($filesystem->has('deeply'));
    }

    /**
     * @test
     * @depends testInstantiable
     */
    public function listing_files_of_a_directory()
    {
        $filesystem = $this->filesystem;
        $filesystem->write('dirname/a.txt', 'contents');
        $filesystem->write('dirname/b/b.txt', 'contents');
        $filesystem->write('dirname/c.txt', 'contents');
        $files = $filesystem->listPaths('', true);
        $expected = ['dirname', 'dirname/a.txt', 'dirname/b', 'dirname/b/b.txt', 'dirname/c.txt'];
        $filesystem->deleteDir('dirname');
        $this->assertEquals($expected, $files);
    }
}