<?php

declare(strict_types=1);

namespace League\Flysystem\ZipArchive;

use PHPUnit\Framework\TestCase;

final class ZipArchivePathNormalizerTest extends TestCase
{
    /**
     * @test
     * @dataProvider userFilePathProvider
     */
    public function normalizing_path_for_a_file(string $prefix, string $path, string $expected): void
    {
        $normalizer = new ZipArchivePathNormalizer($prefix);
        self::assertSame($expected, $normalizer->forFile($path));
    }

    /**
     * @test
     * @dataProvider zipFilePathProvider
     */
    public function inversing_path_for_a_file(string $prefix, string $path, string $expected): void
    {
        $normalizer = new ZipArchivePathNormalizer($prefix);
        self::assertSame($expected, $normalizer->inverseForFile($path));
    }

    /**
     * @test
     * @dataProvider userDirectoryPathProvider
     */
    public function normalizing_path_for_a_directory(string $prefix, string $path, string $expected): void
    {
        $normalizer = new ZipArchivePathNormalizer($prefix);
        self::assertSame($expected, $normalizer->forDirectory($path));
    }

    /**
     * @test
     * @dataProvider zipDirectoryPathProvider
     */
    public function inversing_path_for_a_directory(string $prefix, string $path, string $expected): void
    {
        $normalizer = new ZipArchivePathNormalizer($prefix);
        self::assertSame($expected, $normalizer->inverseForDirectory($path));
    }

    public function userFilePathProvider(): iterable
    {
        yield ['', 'foo.txt', 'foo.txt'];
        yield ['', '/foo.txt', 'foo.txt'];
        yield ['', 'foo/bar.txt', 'foo/bar.txt'];
        yield ['', '/foo/bar.txt', 'foo/bar.txt'];
        yield ['/', 'foo.txt', 'foo.txt'];
        yield ['/', '/foo.txt', 'foo.txt'];
        yield ['/', 'foo/bar.txt', 'foo/bar.txt'];
        yield ['/', '/foo/bar.txt', 'foo/bar.txt'];
        yield ['foo', 'bar.txt', 'foo/bar.txt'];
        yield ['foo', '/bar.txt', 'foo/bar.txt'];
        yield ['foo', 'bar/baz.txt', 'foo/bar/baz.txt'];
        yield ['foo', '/bar/baz.txt', 'foo/bar/baz.txt'];
        yield ['/foo', 'bar.txt', 'foo/bar.txt'];
        yield ['/foo', '/bar.txt', 'foo/bar.txt'];
        yield ['/foo', 'bar/baz.txt', 'foo/bar/baz.txt'];
        yield ['/foo', '/bar/baz.txt', 'foo/bar/baz.txt'];
        yield ['foo/', 'bar.txt', 'foo/bar.txt'];
        yield ['foo/', '/bar.txt', 'foo/bar.txt'];
        yield ['foo/', 'bar/baz.txt', 'foo/bar/baz.txt'];
        yield ['foo/', '/bar/baz.txt', 'foo/bar/baz.txt'];
        yield ['/foo/', 'bar.txt', 'foo/bar.txt'];
        yield ['/foo/', '/bar.txt', 'foo/bar.txt'];
        yield ['/foo/', 'bar/baz.txt', 'foo/bar/baz.txt'];
        yield ['/foo/', '/bar/baz.txt', 'foo/bar/baz.txt'];
    }

    public function zipFilePathProvider(): iterable
    {
        yield ['', 'foo.txt', 'foo.txt'];
        yield ['', 'foo/bar.txt', 'foo/bar.txt'];
        yield ['foo/', 'foo/bar.txt', 'bar.txt'];
        yield ['foo/', 'foo/bar/baz.txt', 'bar/baz.txt'];
    }

    public function userDirectoryPathProvider(): iterable
    {
        yield ['', '', ''];
        yield ['', 'foo', 'foo/'];
        yield ['', '/foo', 'foo/'];
        yield ['', 'foo/', 'foo/'];
        yield ['', '/foo/', 'foo/'];
        yield ['', 'foo/bar', 'foo/bar/'];
        yield ['', '/foo/bar', 'foo/bar/'];
        yield ['', 'foo/bar/', 'foo/bar/'];
        yield ['', '/foo/bar/', 'foo/bar/'];
        yield ['/', '', ''];
        yield ['/', 'foo', 'foo/'];
        yield ['/', '/foo', 'foo/'];
        yield ['/', 'foo/', 'foo/'];
        yield ['/', '/foo/', 'foo/'];
        yield ['/', 'foo/bar', 'foo/bar/'];
        yield ['/', '/foo/bar', 'foo/bar/'];
        yield ['/', 'foo/bar/', 'foo/bar/'];
        yield ['/', '/foo/bar/', 'foo/bar/'];
        yield ['foo', '', 'foo/'];
        yield ['foo', 'bar', 'foo/bar/'];
        yield ['foo', '/bar', 'foo/bar/'];
        yield ['foo', 'bar/', 'foo/bar/'];
        yield ['foo', '/bar/', 'foo/bar/'];
        yield ['foo', 'bar/baz', 'foo/bar/baz/'];
        yield ['foo', '/bar/baz', 'foo/bar/baz/'];
        yield ['foo', 'bar/baz/', 'foo/bar/baz/'];
        yield ['foo', '/bar/baz/', 'foo/bar/baz/'];
        yield ['/foo', '', 'foo/'];
        yield ['/foo', 'bar', 'foo/bar/'];
        yield ['/foo', '/bar', 'foo/bar/'];
        yield ['/foo', 'bar/', 'foo/bar/'];
        yield ['/foo', '/bar/', 'foo/bar/'];
        yield ['/foo', 'bar/baz', 'foo/bar/baz/'];
        yield ['/foo', '/bar/baz', 'foo/bar/baz/'];
        yield ['/foo', 'bar/baz/', 'foo/bar/baz/'];
        yield ['/foo', '/bar/baz/', 'foo/bar/baz/'];
        yield ['foo/', '', 'foo/'];
        yield ['foo/', 'bar', 'foo/bar/'];
        yield ['foo/', '/bar', 'foo/bar/'];
        yield ['foo/', 'bar/', 'foo/bar/'];
        yield ['foo/', '/bar/', 'foo/bar/'];
        yield ['foo/', 'bar/baz', 'foo/bar/baz/'];
        yield ['foo/', '/bar/baz', 'foo/bar/baz/'];
        yield ['foo/', 'bar/baz/', 'foo/bar/baz/'];
        yield ['foo/', '/bar/baz/', 'foo/bar/baz/'];
        yield ['/foo/', '', 'foo/'];
        yield ['/foo/', 'bar', 'foo/bar/'];
        yield ['/foo/', '/bar', 'foo/bar/'];
        yield ['/foo/', 'bar/', 'foo/bar/'];
        yield ['/foo/', '/bar/', 'foo/bar/'];
        yield ['/foo/', 'bar/baz', 'foo/bar/baz/'];
        yield ['/foo/', '/bar/baz', 'foo/bar/baz/'];
        yield ['/foo/', 'bar/baz/', 'foo/bar/baz/'];
        yield ['/foo/', '/bar/baz/', 'foo/bar/baz/'];
    }

    public function zipDirectoryPathProvider(): iterable
    {
        yield ['', '', ''];
        yield ['', 'foo/', 'foo'];
        yield ['', 'foo/bar/', 'foo/bar'];
        yield ['foo/', '', ''];
        yield ['foo/', 'foo/bar/', 'bar'];
        yield ['foo/', 'foo/bar/baz/', 'bar/baz'];
    }
}
