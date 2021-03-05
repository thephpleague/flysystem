<?php

declare(strict_types=1);

namespace League\Flysystem;

use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\Local\LocalFilesystemAdapter;
use LogicException;

class ArrayAdapterTest extends FilesystemAdapterTestCase
{
    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        return new ArrayAdapter(
            [
                'main' => new LocalFilesystemAdapter(__DIR__ . '/../test_files/root'),
                'first' => new LocalFilesystemAdapter(__DIR__ . '/../test_files/namespace1'),
                'second' => new LocalFilesystemAdapter(__DIR__ . '/../test_files/namespace2'),
            ],
            'main'
        );
    }

    public function testNamespace()
    {
        $contentsFirst = $this->adapter()->read('@first\foo.txt');
        $contentsSecond = $this->adapter()->read('@second/bar.txt');

        $this->assertEquals('foo', $contentsFirst);
        $this->assertEquals('bar', $contentsSecond);
    }

    public function testCopyBetweenNamespace()
    {
        if ($this->adapter()->fileExists('@first/copy.txt')) {
            $this->adapter()->delete('@first/copy.txt');
            $this->assertFalse($this->adapter()->fileExists('@first/copy.txt'));
        }

        $this->adapter()->write('@first/copy.txt', 'foo', new Config());
        $this->adapter()->copy('@first/copy.txt', '@second/copy.txt', new Config());

        $this->assertTrue($this->adapter()->fileExists('@first/copy.txt'));
        $this->assertTrue($this->adapter()->fileExists('@second/copy.txt'));

        $this->adapter()->delete('@first/copy.txt');
        $this->adapter()->delete('@second/copy.txt');
    }

    public function testMoveBetweenNamespace()
    {
        if ($this->adapter()->fileExists('@first/move.txt')) {
            $this->adapter()->delete('@first/move.txt');
            $this->assertFalse($this->adapter()->fileExists('@first/move.txt'));
        }

        $this->adapter()->write('@first/move.txt', 'foo', new Config());
        $this->adapter()->move('@first/move.txt', '@second/move.txt', new Config());

        $this->assertFalse($this->adapter()->fileExists('@first/move.txt'));
        $this->assertTrue($this->adapter()->fileExists('@second/move.txt'));

        $this->adapter()->delete('@second/move.txt');
    }

    public function testReadWithoutDefaultNamespaceAndNotSpecified()
    {
        $this->expectException(LogicException::class);

        $adapter = new ArrayAdapter(['main' => new LocalFilesystemAdapter(__DIR__ . '/../test_files/namespace1')]);
        $adapter->read('/foo.txt');
    }

    public function testWithoutDefaultNamespace()
    {
        $adapter = new ArrayAdapter(['main' => new LocalFilesystemAdapter(__DIR__ . '/../test_files/namespace1')]);
        $contents = $adapter->read('@main/foo.txt');

        $this->assertEquals('foo', $contents);
    }
}
