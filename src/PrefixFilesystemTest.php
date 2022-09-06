<?php

namespace League\Flysystem;

use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;

class PrefixFilesystemTest extends TestCase
{
    public function testPrefix(): void
    {
        $fs = new Filesystem(new InMemoryFilesystemAdapter());
        $prefix = new PrefixFilesystem($fs, 'foo');

        $prefix->write('foo.txt', 'bla');
        static::assertTrue($prefix->fileExists('foo.txt'));
        static::assertTrue($prefix->has('foo.txt'));
        static::assertFalse($prefix->directoryExists('foo.txt'));
        static::assertTrue($fs->has('foo/foo.txt'));
        static::assertFalse($fs->directoryExists('foo/foo.txt'));

        static::assertSame('bla', $prefix->read('foo.txt'));
        static::assertSame('bla', stream_get_contents($prefix->readStream('foo.txt')));
        static::assertSame('text/plain', $prefix->mimeType('foo.txt'));
        static::assertSame(3, $prefix->fileSize('foo.txt'));
        static::assertSame(Visibility::PUBLIC, $prefix->visibility('foo.txt'));
        $prefix->setVisibility('foo.txt', Visibility::PRIVATE);
        static::assertSame(Visibility::PRIVATE, $prefix->visibility('foo.txt'));
        static::assertEqualsWithDelta($prefix->lastModified('foo.txt'), time(), 2);

        $prefix->copy('foo.txt', 'bla.txt');
        static::assertTrue($prefix->has('bla.txt'));

        $prefix->createDirectory('dir');
        static::assertTrue($prefix->directoryExists('dir'));
        static::assertFalse($prefix->directoryExists('dir2'));
        $prefix->deleteDirectory('dir');
        static::assertFalse($prefix->directoryExists('dir'));

        $prefix->move('bla.txt', 'bla2.txt');
        static::assertFalse($prefix->has('bla.txt'));
        static::assertTrue($prefix->has('bla2.txt'));

        $prefix->delete('bla2.txt');
        static::assertFalse($prefix->has('bla2.txt'));

        $prefix->createDirectory('test');

        $files = $prefix->listContents('', true)->toArray();
        static::assertCount(2, $files);
    }

    public function testWriteStream(): void
    {
        $fs = new Filesystem(new InMemoryFilesystemAdapter());
        $prefix = new PrefixFilesystem($fs, 'foo');
        $tmpFile = sys_get_temp_dir() . '/' . uniqid('test', true);
        file_put_contents($tmpFile, 'test');

        $prefix->writeStream('a.txt', fopen($tmpFile, 'rb'));

        static::assertTrue($prefix->fileExists('a.txt'));
        static::assertSame('test', $prefix->read('a.txt'));
        static::assertSame('test', stream_get_contents($prefix->readStream('a.txt')));

        unlink($tmpFile);
    }

    public function testEmptyPrefix(): void
    {
        static::expectException(\InvalidArgumentException::class);
        new PrefixFilesystem(new Filesystem(new InMemoryFilesystemAdapter()), '');
    }
}
