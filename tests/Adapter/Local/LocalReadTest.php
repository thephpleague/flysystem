<?php

namespace League\Flysystem\Tests\Adapter\Local;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Test\Adapter\Polyfill\WriteFixturesTrait;
use League\Flysystem\Test\Adapter\ReadTestCase;

class LocalReadTest extends ReadTestCase
{
    use WriteFixturesTrait;

    /**
     * {@inheritDoc}
     */
    protected function getRoot()
    {
        return __DIR__.'/files/';
    }

    /**
     * {@inheritDoc}
     */
    protected function getAdapter($root)
    {
        return new Local($root);
    }

    public function tearDown()
    {
        $it = new \RecursiveDirectoryIterator($this->root, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
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
        rmdir($this->root);
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

    public function testApplyPathPrefix()
    {
        $this->adapter->setPathPrefix('');
        $this->assertEquals('', $this->adapter->applyPathPrefix(''));
    }
}
