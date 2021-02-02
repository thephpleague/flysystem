<?php

declare(strict_types=1);

namespace League\Flysystem;

use PHPUnit\Framework\TestCase;

class PathPrefixerTest extends TestCase
{
    /**
     * @test
     */
    public function path_prefixing_with_a_prefix(): void
    {
        $prefixer = new PathPrefixer('prefix');
        $prefixedPath = $prefixer->prefixPath('some/path.txt');
        $this->assertEquals('prefix/some/path.txt', $prefixedPath);
    }

    /**
     * @test
     */
    public function path_stripping_with_a_prefix(): void
    {
        $prefixer = new PathPrefixer('prefix');
        $strippedPath = $prefixer->stripPrefix('prefix/some/path.txt');
        $this->assertEquals('some/path.txt', $strippedPath);
    }

    /**
     * @test
     * @dataProvider dpRootPaths
     */
    public function an_absolute_root_path_is_supported(string $rootPath, string $separator, string $path, string $expectedPath): void
    {
        $prefixer = new PathPrefixer($rootPath, $separator);
        $prefixedPath = $prefixer->prefixPath($path);
        $this->assertEquals($expectedPath, $prefixedPath);
    }

    public function dpRootPaths(): iterable
    {
        yield "unix-style root path" => ['/', '/', 'path.txt', '/path.txt'];
        yield "windows-style root path" => ['\\', '\\', 'path.txt', '\\path.txt'];
    }

    /**
     * @test
     */
    public function path_stripping_is_reversable(): void
    {
        $prefixer = new PathPrefixer('prefix');
        $strippedPath = $prefixer->stripPrefix('prefix/some/path.txt');
        $this->assertEquals('prefix/some/path.txt', $prefixer->prefixPath($strippedPath));
        $prefixedPath = $prefixer->prefixPath('some/path.txt');
        $this->assertEquals('some/path.txt', $prefixer->stripPrefix($prefixedPath));
    }

    /**
     * @test
     */
    public function prefixing_without_a_prefix(): void
    {
        $prefixer = new PathPrefixer('');

        $path = $prefixer->prefixPath('path/to/prefix.txt');
        $this->assertEquals('path/to/prefix.txt', $path);

        $path = $prefixer->prefixPath('/path/to/prefix.txt');
        $this->assertEquals('path/to/prefix.txt', $path);
    }

    /**
     * @test
     */
    public function prefixing_for_a_directory(): void
    {
        $prefixer = new PathPrefixer('/prefix');

        $path = $prefixer->prefixDirectoryPath('something');
        $this->assertEquals('/prefix/something/', $path);
        $path = $prefixer->prefixDirectoryPath('');
        $this->assertEquals('/prefix/', $path);
    }

    /**
     * @test
     */
    public function prefixing_for_a_directory_without_a_prefix(): void
    {
        $prefixer = new PathPrefixer('');

        $path = $prefixer->prefixDirectoryPath('something');
        $this->assertEquals('something/', $path);
        $path = $prefixer->prefixDirectoryPath('');
        $this->assertEquals('', $path);
    }

    /**
     * @test
     */
    public function stripping_a_directory_prefix(): void
    {
        $prefixer = new PathPrefixer('/something/');

        $path = $prefixer->stripDirectoryPrefix('/something/this/');
        $this->assertEquals('this', $path);
        $path = $prefixer->stripDirectoryPrefix('/something/and-this\\');
        $this->assertEquals('and-this', $path);
    }
}
