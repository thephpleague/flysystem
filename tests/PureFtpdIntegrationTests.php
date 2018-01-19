<?php

use League\Flysystem\Adapter\Ftpd;
use League\Flysystem\Filesystem;
use League\Flysystem\Plugin\ListFiles;
use PHPUnit\Framework\TestCase;

class PureFtpdIntegrationTests extends TestCase
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @before
     */
    public function setup_filesystem()
    {
        $adapter = new Ftpd(['host' => 'localhost', 'username' => 'bob', 'password' => 'test']);
        $this->filesystem = new Filesystem($adapter);
    }

    /**
     * @test
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

    public function listing_files_of_a_directory()
    {
        $filesystem = $this->filesystem;
        $filesystem->write('dirname/a.txt', 'contents');
        $filesystem->write('dirname/b.txt', 'contents');
        $filesystem->write('dirname/c.txt', 'contents');
        $filesystem->addPlugin(new ListFiles());
        $files = $filesystem->listFiles('dirname');
        $expected = ['dirname/a.txt', 'dirname/b.txt', 'dirname/c.txt'];
        $filesystem->deleteDir('dirname');
        $this->assertEquals($expected, $files);
    }
}