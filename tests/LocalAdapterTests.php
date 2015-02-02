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
                unlink($file->getRealPath());
            }
        }
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
}
