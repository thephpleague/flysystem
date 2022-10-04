<?php

namespace League\Flysystem\PathPrefixing;

use League\Flysystem\Config;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\UnableToGeneratePublicUrl;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\Visibility;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

class PathPrefixedAdapterTest extends TestCase
{
    public function testPrefix(): void
    {
        $adapter = new InMemoryFilesystemAdapter();
        $prefix = new PathPrefixedAdapter($adapter, 'foo');

        $prefix->write('foo.txt', 'bla', new Config);
        static::assertTrue($prefix->fileExists('foo.txt'));
        static::assertFalse($prefix->directoryExists('foo.txt'));
        static::assertTrue($adapter->fileExists('foo/foo.txt'));
        static::assertFalse($adapter->directoryExists('foo/foo.txt'));

        static::assertSame('bla', $prefix->read('foo.txt'));
        static::assertSame('bla', stream_get_contents($prefix->readStream('foo.txt')));
        static::assertSame('text/plain', $prefix->mimeType('foo.txt')->mimeType());
        static::assertSame(3, $prefix->fileSize('foo.txt')->fileSize());
        static::assertSame(Visibility::PUBLIC, $prefix->visibility('foo.txt')->visibility());
        $prefix->setVisibility('foo.txt', Visibility::PRIVATE);
        static::assertSame(Visibility::PRIVATE, $prefix->visibility('foo.txt')->visibility());
        static::assertEqualsWithDelta($prefix->lastModified('foo.txt')->lastModified(), time(), 2);

        $prefix->copy('foo.txt', 'bla.txt', new Config);
        static::assertTrue($prefix->fileExists('bla.txt'));

        $prefix->createDirectory('dir', new Config());
        static::assertTrue($prefix->directoryExists('dir'));
        static::assertFalse($prefix->directoryExists('dir2'));
        $prefix->deleteDirectory('dir');
        static::assertFalse($prefix->directoryExists('dir'));

        $prefix->move('bla.txt', 'bla2.txt', new Config());
        static::assertFalse($prefix->fileExists('bla.txt'));
        static::assertTrue($prefix->fileExists('bla2.txt'));

        $prefix->delete('bla2.txt');
        static::assertFalse($prefix->fileExists('bla2.txt'));

        $prefix->createDirectory('test', new Config());

        $files = iterator_to_array($prefix->listContents('', true));
        static::assertCount(2, $files);
    }

    public function testWriteStream(): void
    {
        $adapter = new InMemoryFilesystemAdapter();
        $prefix = new PathPrefixedAdapter($adapter, 'foo');
        $tmpFile = sys_get_temp_dir() . '/' . uniqid('test', true);
        file_put_contents($tmpFile, 'test');

        $prefix->writeStream('a.txt', fopen($tmpFile, 'rb'), new Config());

        static::assertTrue($prefix->fileExists('a.txt'));
        static::assertSame('test', $prefix->read('a.txt'));
        static::assertSame('test', stream_get_contents($prefix->readStream('a.txt')));

        unlink($tmpFile);
    }

    public function testEmptyPrefix(): void
    {
        static::expectException(\InvalidArgumentException::class);
        new PathPrefixedAdapter(new InMemoryFilesystemAdapter(), '');
    }

    /**
     * @test
     */
    public function generating_a_public_url(): void
    {
        $adapter = new class() extends InMemoryFilesystemAdapter implements PublicUrlGenerator {
            public function publicUrl(string $path, Config $config): string
            {
                return 'memory://' . ltrim($path, '/');
            }
        };
        $prefixedAdapter = new PathPrefixedAdapter($adapter, 'prefix');

        $url = $prefixedAdapter->publicUrl('/path.txt', new Config());

        self::assertEquals('memory://prefix/path.txt', $url);
    }

    /**
     * @test
     */
    public function failing_to_generate_a_public_url(): void
    {
        $prefixedAdapter = new PathPrefixedAdapter(new InMemoryFilesystemAdapter(), 'prefix');

        $this->expectException(UnableToGeneratePublicUrl::class);

        $prefixedAdapter->publicUrl('/path.txt', new Config());
    }
}
