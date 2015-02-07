<?php

namespace League\Flysystem;

class FileTests extends \PHPUnit_Framework_TestCase
{
    /** @var Filesystem */
    protected $filesystem;

    public function setup()
    {
        clearstatcache();
        $fs = new Adapter\Local(__DIR__.'/');
        $fs->deleteDir('files');
        $fs->createDir('files', new Config());
        $fs->write('file.txt', 'contents', new Config());
        $this->filesystem = new Filesystem($fs);
    }

    public function tearDown()
    {
        try {
            $this->filesystem->delete('file.txt');
        } catch (FileNotFoundException $e) {
        }
        $this->filesystem->deleteDir('files');
    }

    /**
     * @return File
     */
    protected function getFile()
    {
        return $this->filesystem->get('file.txt');
    }

    public function testExists()
    {
        $file = $this->getFile();
        $this->assertTrue($file->exists());
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

    public function testWrite()
    {
        $file = new File();
        $this->filesystem->get('files/new.txt', $file);
        $file->write('new contents');
        $this->assertEquals('new contents', $file->read());
    }

    public function testWriteStream()
    {
        $file = new File();
        $this->filesystem->get('files/new.txt', $file);
        $resource = tmpfile();
        fwrite($resource, 'stream contents');
        $file->writeStream($resource);
        $this->assertEquals('stream contents', $file->read());
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

    public function testPut()
    {
        $file = new File();
        $this->filesystem->get('files/new.txt', $file);
        $file->put('new contents');
        $this->assertEquals('new contents', $file->read());
        $file->put('updated content');
        $this->assertEquals('updated content', $file->read());
    }

    public function testPutStream()
    {
        $file = new File();
        $this->filesystem->get('files/new.txt', $file);

        $resource = tmpfile();
        fwrite($resource, 'stream contents');
        $file->putStream($resource);
        fclose($resource);

        $this->assertEquals('stream contents', $file->read());

        $resource = tmpfile();
        fwrite($resource, 'updated stream contents');
        $file->putStream($resource);
        fclose($resource);

        $this->assertEquals('updated stream contents', $file->read());
    }

    public function testRename()
    {
        $file = $this->getFile();
        $result = $file->rename('files/renamed.txt');
        $this->assertTrue($result);
        $this->assertFalse($this->filesystem->has('file.txt'));
        $this->assertTrue($this->filesystem->has('files/renamed.txt'));
        $this->assertEquals('files/renamed.txt', $file->getPath());
    }

    public function testRenameFails()
    {
        $adapter = $this->getMock('League\Flysystem\AdapterInterface');
        $adapter
            ->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive(
                ['file.txt'],
                ['files/renamed.txt']
            )
            ->willReturnOnConsecutiveCalls(true, false);
        $adapter
            ->expects($this->once())
            ->method('rename')
            ->with('file.txt', 'files/renamed.txt')
            ->willReturn(false);

        $filesystem = new Filesystem($adapter);
        /** @var File $file */
        $file = $filesystem->get('file.txt', new File());
        $result = $file->rename('files/renamed.txt');
        $this->assertFalse($result);
        $this->assertEquals('file.txt', $file->getPath());
    }

    public function testCopy()
    {
        $file = $this->getFile();
        $copied = $file->copy('files/copied.txt');
        $this->assertTrue($this->filesystem->has('file.txt'));
        $this->assertTrue($this->filesystem->has('files/copied.txt'));
        $this->assertEquals('file.txt', $file->getPath());
        $this->assertEquals('files/copied.txt', $copied->getPath());
    }

    public function testCopyFails()
    {
        $adapter = $this->getMock('League\Flysystem\AdapterInterface');
        $adapter
            ->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive(
                ['file.txt'],
                ['files/copied.txt']
            )
            ->willReturnOnConsecutiveCalls(true, false);
        $adapter
            ->expects($this->once())
            ->method('copy')
            ->with('file.txt', 'files/copied.txt')
            ->willReturn(false);

        $filesystem = new Filesystem($adapter);
        /** @var File $file */
        $file = $filesystem->get('file.txt', new File());
        $result = $file->copy('files/copied.txt');
        $this->assertFalse($result);
    }

    public function testTimestamp()
    {
        $file = $this->getFile();
        $timestamp = $this->filesystem->getTimestamp($file->getPath());
        $this->assertEquals($timestamp, $file->getTimestamp());
    }

    public function testMimetype()
    {
        $file = $this->getFile();
        $mimetype = $this->filesystem->getMimetype($file->getPath());
        $this->assertEquals($mimetype, $file->getMimetype());
    }

    public function testVisibility()
    {
        $file = $this->getFile();
        $visibility = $this->filesystem->getVisibility($file->getPath());
        $this->assertEquals($visibility, $file->getVisibility());
    }

    public function testMetadata()
    {
        $file = $this->getFile();
        $metadata = $this->filesystem->getMetadata($file->getPath());
        $this->assertEquals($metadata, $file->getMetadata());
    }

    public function testSize()
    {
        $file = $this->getFile();
        $size = $this->filesystem->getSize($file->getPath());
        $this->assertEquals($size, $file->getSize());
    }

    public function testDelete()
    {
        $file = $this->getFile();
        $file->delete();
        $this->assertFalse($this->filesystem->has('file.txt'));
    }
}
