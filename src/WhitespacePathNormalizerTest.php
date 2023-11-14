<?php

declare(strict_types=1);

namespace League\Flysystem;

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
     * @return array<array<string>>
     */
    public static function pathProvider(): array
    {
        return [
            ['.', ''],
            ['/path/to/dir/.', 'path/to/dir'],
            ['/dirname/', 'dirname'],
            ['dirname/..', ''],
            ['dirname/../', ''],
            ['dirname./', 'dirname.'],
            ['dirname/./', 'dirname'],
            ['dirname/.', 'dirname'],
            ['./dir/../././', ''],
            ['/something/deep/../../dirname', 'dirname'],
            ['00004869/files/other/10-75..stl', '00004869/files/other/10-75..stl'],
            ['/dirname//subdir///subsubdir', 'dirname/subdir/subsubdir'],
            ['\dirname\\\\subdir\\\\\\subsubdir', 'dirname/subdir/subsubdir'],
            ['\\\\some\shared\\\\drive', 'some/shared/drive'],
            ['C:\dirname\\\\subdir\\\\\\subsubdir', 'C:/dirname/subdir/subsubdir'],
            ['C:\\\\dirname\subdir\\\\subsubdir', 'C:/dirname/subdir/subsubdir'],
            ['example/path/..txt', 'example/path/..txt'],
            ['\\example\\path.txt', 'example/path.txt'],
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
