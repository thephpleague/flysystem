<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Config;

function fopen($result)
{
    if (substr($result, -5) === 'false') {
        return false;
    }

    if (substr($result, -5) === 'dummy') {
        return 'dummy';
    }

    return call_user_func_array('fopen', func_get_args());
}

function fwrite($result)
{
    if (is_string($result)) {
        return 'dummy';
    }

    return call_user_func_array('fwrite', func_get_args());
}

function fclose($result)
{
    if (is_string($result) and substr($result, -5) === 'dummy') {
        return false;
    }

    return call_user_func_array('fclose', func_get_args());
}

function mkdir($pathname, $mode = 0777, $recursive = false, $context = null)
{
    if (strpos($pathname, 'fail.plz') !== false) {
        return false;
    }

    return call_user_func_array('mkdir', func_get_args());
}

class LocalAdapterTests extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Local
     */
    protected $adapter;

    protected $root;

    public function setup()
    {
        $this->root = __DIR__.'/files/';
        $this->adapter = new Local($this->root);
    }

    public function teardown()
    {
        $it = new \RecursiveDirectoryIterator($this->root, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it,
                     \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->getFilename() === '.' || $file->getFilename() === '..') {
                continue;
            }
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getPathname());
            }
        }
    }

    public function testHasWithDir()
    {
        $this->adapter->createDir('0', new Config());
        $this->assertTrue($this->adapter->has('0'));
        $this->adapter->deleteDir('0');
    }

    public function testHasWithFile()
    {
        $adapter = $this->adapter;
        $adapter->write('file.txt', 'content', new Config());
        $this->assertTrue($adapter->has('file.txt'));
        $adapter->delete('file.txt');
    }

    public function testReadStream()
    {
        $adapter = $this->adapter;
        $adapter->write('file.txt', 'contents', new Config());
        $result = $adapter->readStream('file.txt');
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('stream', $result);
        $this->assertInternalType('resource', $result['stream']);
        fclose($result['stream']);
        $adapter->delete('file.txt');
    }

    public function testWriteStream()
    {
        $adapter = $this->adapter;
        $temp = tmpfile();
        fwrite($temp, 'dummy');
        rewind($temp);
        $adapter->writeStream('dir/file.txt', $temp, new Config(['visibility' => 'public']));
        fclose($temp);
        $this->assertTrue($adapter->has('dir/file.txt'));
        $result = $adapter->read('dir/file.txt');
        $this->assertEquals('dummy', $result['contents']);
        $adapter->deleteDir('dir');
    }

    public function testListingNonexistingDirectory()
    {
        $result = $this->adapter->listContents('nonexisting/directory');
        $this->assertEquals([], $result);
    }

    public function testUpdateStream()
    {
        $adapter = $this->adapter;
        $adapter->write('file.txt', 'initial', new Config());
        $temp = tmpfile();
        fwrite($temp, 'dummy');
        $adapter->updateStream('file.txt', $temp, new Config());
        fclose($temp);
        $this->assertTrue($adapter->has('file.txt'));
        $adapter->delete('file.txt');
    }

    public function testCreateZeroDir()
    {
        $this->adapter->createDir('0', new Config());
        $this->assertTrue(is_dir($this->adapter->applyPathPrefix('0')));
        $this->adapter->deleteDir('0');
    }

    public function testCopy()
    {
        $adapter = $this->adapter;
        $adapter->write('file.ext', 'content', new Config(['visibility' => 'public']));
        $this->assertTrue($adapter->copy('file.ext', 'new.ext'));
        $this->assertTrue($adapter->has('new.ext'));
        $adapter->delete('file.ext');
        $adapter->delete('new.ext');
    }

    public function testFailingStreamCalls()
    {
        $this->assertFalse($this->adapter->writeStream('false', tmpfile(), new Config()));
        $this->assertFalse($this->adapter->writeStream('dummy', tmpfile(), new Config()));
    }

    public function testNullPrefix()
    {
        $this->adapter->setPathPrefix('');
        $path = 'some/path.ext';
        $this->assertEquals($path, $this->adapter->applyPathPrefix($path));
        $this->assertEquals($path, $this->adapter->removePathPrefix($path));
    }

    public function testGetPathPrefix()
    {
        $this->assertEquals(realpath($this->root).DIRECTORY_SEPARATOR, $this->adapter->getPathPrefix());
    }

    public function testRenameToNonExistsingDirectory()
    {
        $this->adapter->write('file.txt', 'contents', new Config());
        $dirname = uniqid();
        $this->assertFalse(is_dir($this->root.DIRECTORY_SEPARATOR.$dirname));
        $this->assertTrue($this->adapter->rename('file.txt', $dirname.'/file.txt'));
    }

    public function testNotWritableRoot()
    {
        if (IS_WINDOWS) {
            $this->markTestSkipped("File permissions not supported on Windows.");
        }

        try {
            $root = __DIR__.'/files/not-writable';
            mkdir($root, 0000, true);
            $this->setExpectedException('LogicException');
            new Local($root);
        } catch (\Exception $e) {
            rmdir($root);
            throw $e;
        }
    }

    public function testListContents()
    {
        $this->adapter->write('dirname/file.txt', 'contents', new Config());
        $contents = $this->adapter->listContents('dirname', false);
        $this->assertCount(1, $contents);
        $this->assertArrayHasKey('type', $contents[0]);
    }

    public function testGetSize()
    {
        $this->adapter->write('dummy.txt', '1234', new Config());
        $result = $this->adapter->getSize('dummy.txt');
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('size', $result);
        $this->assertEquals(4, $result['size']);
    }

    public function testGetTimestamp()
    {
        $this->adapter->write('dummy.txt', '1234', new Config());
        $result = $this->adapter->getTimestamp('dummy.txt');
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertInternalType('int', $result['timestamp']);
    }

    public function testGetMimetype()
    {
        $this->adapter->write('text.txt', 'contents', new Config());
        $result = $this->adapter->getMimetype('text.txt');
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('mimetype', $result);
        $this->assertEquals('text/plain', $result['mimetype']);
    }

    public function testCreateDirFail()
    {
        $this->assertFalse($this->adapter->createDir('fail.plz', new Config()));
    }

    public function testDeleteDir()
    {
        $this->adapter->write('nested/dir/path.txt', 'contents', new Config());
        $this->assertTrue(is_dir(__DIR__.'/files/nested/dir'));
        $this->adapter->deleteDir('nested');
        $this->assertFalse($this->adapter->has('nested/dir/path.txt'));
        $this->assertFalse(is_dir(__DIR__.'/files/nested/dir'));
    }

    public function testVisibilityPublic()
    {
        if (IS_WINDOWS) {
            $this->markTestSkipped("Visibility not supported on Windows.");
        }

        $this->adapter->write('path.txt', 'contents', new Config());
        $this->adapter->setVisibility('path.txt', 'public');
        $output = $this->adapter->getVisibility('path.txt');
        $this->assertInternalType('array', $output);
        $this->assertArrayHasKey('visibility', $output);
        $this->assertEquals('public', $output['visibility']);
    }

    public function testVisibilityPrivate()
    {
        if (IS_WINDOWS) {
            $this->markTestSkipped("Visibility not supported on Windows.");
        }

        $this->adapter->write('path.txt', 'contents', new Config());
        $this->adapter->setVisibility('path.txt', 'private');
        $output = $this->adapter->getVisibility('path.txt');
        $this->assertInternalType('array', $output);
        $this->assertArrayHasKey('visibility', $output);
        $this->assertEquals('private', $output['visibility']);
    }

    public function testApplyPathPrefix()
    {
        $this->adapter->setPathPrefix('');
        $this->assertEquals('', $this->adapter->applyPathPrefix(''));
    }

    public function testConstructorWithLink()
    {
        $target = __DIR__.'/files/';
        $link = __DIR__.'/link_to_files';
        symlink($target, $link);

        $adapter = new Local($link);
        $this->assertEquals($target, $adapter->getPathPrefix());
        unlink($link);
    }

    /**
     * @expectedException League\Flysystem\NotSupportedException
     */
    public function testLinkCausedUnsupportedException()
    {
        $root = __DIR__.'/files/';
        $original = $root.'original.txt';
        $link = $root.'link.txt';
        file_put_contents($original, 'something');
        symlink($original, $link);
        $adapter = new Local($root);
        $adapter->listContents();
    }
}
