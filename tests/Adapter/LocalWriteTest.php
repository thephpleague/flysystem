<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Config;
use League\Flysystem\Test\Adapter\Polyfill\WriteFixturesTrait;
use League\Flysystem\Test\Adapter\WriteTestCase;

/**** overwrite some system functions in League\Flysystem\Adapter namespace ****/

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

class LocalWriteTests extends WriteTestCase
{
    use WriteFixturesTrait;

    /**
     * {@inheritDoc}
     */
    protected function getRoot()
    {
        return __DIR__.DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR;
    }

    /**
     * {@inheritDoc}
     */
    protected function getAdapter($root)
    {
        return new Local($root);
    }

    /**
     * {@inheritDoc}
     */
    protected function assertAdapterFileContains($location, $contents)
    {
        $absPath = $this->root.str_replace('/', DIRECTORY_SEPARATOR, $location);
        $this->assertTrue(file_exists($absPath));
        $this->assertEquals($contents, file_get_contents($absPath));
    }

    /**
     * Check that $location exists and is a directory.
     *
     * @param string $location Path to the directory.
     */
    protected function assertAdapterDirectoryExists($location)
    {
        $absPath = $this->root.str_replace('/', DIRECTORY_SEPARATOR, $location);
        $this->assertTrue(is_dir($absPath));
    }

    /**
     * Check that $location does not exist.
     *
     * @param string $location Path to the directory.
     */
    protected function assertNotExist($location)
    {
        $absPath = $this->root.str_replace('/', DIRECTORY_SEPARATOR, $location);
        $this->assertFalse(file_exists($absPath));
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

    /**** Additional edge cases tests ****/

    public function testFailingStreamCalls()
    {
        $this->assertFalse($this->adapter->writeStream('false', tmpfile(), new Config()));
        $this->assertFalse($this->adapter->writeStream('dummy', tmpfile(), new Config()));
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
}
