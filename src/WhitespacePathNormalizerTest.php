<?php

declare(strict_types=1);

namespace League\Flysystem;

use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;

class WhitespacePathNormalizerTest extends TestCase
{
    /**
     * @var WhitespacePathNormalizer
     */
    private $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new WhitespacePathNormalizer();
    }

    /**
     * @test
     *
     * @dataProvider  pathProvider
     */
    public function path_normalizing(string $input, string $expected): void
    {
        $result = $this->normalizer->normalizePath($input);
        $double = $this->normalizer->normalizePath($this->normalizer->normalizePath($input));
        $this->assertEquals($expected, $result);
        $this->assertEquals($expected, $double);
    }

    /**
     * @test
     *
     * @dataProvider  relativePathProvider
     */
    public function relative_path_normalizing(string $input, string $expected): void
    {
        $result = $this->normalizer->normalizePath($input);
        $double = $this->normalizer->normalizePath($this->normalizer->normalizePath($input));
        $this->assertEquals($expected, $result);
        $this->assertEquals($expected, $double);
    }

    /**
     * @test
     *
     * @dataProvider  relativePathProvider
     */
    public function not_allowing_relative_path_traversal_through_configuration(string $input): void
    {
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter(), ['allow_relative_path_traversal' => false]);

        $this->expectExceptionObject(PathTraversalDetected::forPath($input));

        $filesystem->write($input, 'foobar');
    }

    /**
     * @test
     *
     * @dataProvider  relativePathProvider
     */
    public function rejecting_relative_paths(string $input): void
    {
        $this->normalizer = new WhitespacePathNormalizer(false);

        $this->expectExceptionObject(PathTraversalDetected::forPath($input));

        $this->normalizer->normalizePath($input);
    }

    /**
     * @return array<array<string>>
     */
    public static function pathProvider(): array
    {
        return [
            ['.', ''],
            ['/path/to/dir/.', 'path/to/dir'],
            ['/dirname/', 'dirname'],
            ['dirname./', 'dirname.'],
            ['dirname/./', 'dirname'],
            ['dirname/.', 'dirname'],
            ['00004869/files/other/10-75..stl', '00004869/files/other/10-75..stl'],
            ['/dirname//subdir///subsubdir', 'dirname/subdir/subsubdir'],
            ['\dirname\\\\subdir\\\\\\subsubdir', 'dirname/subdir/subsubdir'],
            ['\\\\some\shared\\\\drive', 'some/shared/drive'],
            ['C:\dirname\\\\subdir\\\\\\subsubdir', 'C:/dirname/subdir/subsubdir'],
            ['C:\\\\dirname\subdir\\\\subsubdir', 'C:/dirname/subdir/subsubdir'],
            ['example/path/..txt', 'example/path/..txt'],
            ['\\example\\path.txt', 'example/path.txt'],
        ];
    }

    /**
     * @return array<array<string>>
     */
    public static function relativePathProvider(): array
    {
        return [
            ['dirname/..', ''],
            ['dirname/../', ''],
            ['./dir/../././', ''],
            ['/something/deep/../../dirname', 'dirname'],
            ['\\example\\..\\path.txt', 'path.txt'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider invalidPathProvider
     */
    public function guarding_against_path_traversal(string $input): void
    {
        $this->expectException(PathTraversalDetected::class);
        $this->normalizer->normalizePath($input);
    }

    /**
     * @test
     *
     * @dataProvider dpFunkyWhitespacePaths
     */
    public function rejecting_funky_whitespace(string $path): void
    {
        self::expectException(CorruptedPathDetected::class);
        $this->normalizer->normalizePath($path);
    }

    public static function dpFunkyWhitespacePaths(): iterable
    {
        return [["some\0/path.txt"], ["s\x09i.php"]];
    }

    /**
     * @return array<array<string>>
     */
    public static function invalidPathProvider(): array
    {
        return [
            ['something/../../../hehe'],
            ['/something/../../..'],
            ['..'],
            ['something\\..\\..'],
            ['\\something\\..\\..\\dirname'],
        ];
    }
}
