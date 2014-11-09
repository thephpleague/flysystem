<?php

namespace League\Flysystem;

class FileTests extends \PHPUnit_Framework_TestCase
{
    protected $filesystem;

    public function setup()
    {
        clearstatcache();
        $fs = new Adapter\Local(__DIR__.'/');
        $fs->deleteDir('files');
        $fs->createDir('files', new Config);
        $fs->write('file.txt', 'contents', new Config);
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
