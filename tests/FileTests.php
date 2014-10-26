<?php

namespace League\Flysystem\Tests;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use PHPUnit_Framework_TestCase;

class FileTests extends PHPUnit_Framework_TestCase
{
    protected $filesystem;

    public function setup()
    {
        clearstatcache();
        $fs = new Local(__DIR__.'/');
        $fs->deleteDir('files');
        $fs->createDir('files');
        $fs->write('file.txt', 'contents');
        $this->filesystem = new Filesystem($fs);
    }

    public function tearDown()
    {
        $this->filesystem->delete('file.txt');
        $this->filesystem->deleteDir('files');
    }

    protected function getFile()
    {
        return $this->filesystem->get('file.txt');
    }

    public function testRead()
    {
        $file = $this->getFile();
        $contents = $file->read();
        $this->assertEquals('contents', $contents);
    }

    public function testReadSteam()
    {
        $file = $this->getFile();
        $this->assertInternalType('resource', $file->readStream());
    }

    public function testUpdate()
    {
        $file = $this->getFile();
        $file->update('new contents');
        $this->assertEquals('new contents', $file->read());
    }

    public function testUpdateStream()
    {
        $file = $this->getFile();
        $resource = tmpfile();
        fwrite($resource, 'stream contents');
        $file->updateStream($resource);
        fclose($resource);
        $this->assertEquals('stream contents', $file->read());
    }
}
