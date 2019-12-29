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
     * @dataProvider  pathProvider
     */
    public function path_normalizing(string $input, string $expected)
    {
        $result = $this->normalizer->normalizePath($input);
        $double = $this->normalizer->normalizePath($this->normalizer->normalizePath($input));
        $this->assertEquals($expected, $result);
        $this->assertEquals($expected, $double);
    }

    public function pathProvider()
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
            ["some\0/path.txt", 'some/path.txt'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidPathProvider
     */
    public function guarding_against_path_traversal(string $input)
    {
        $this->expectException(PathTraversalDetected::class);
        $this->normalizer->normalizePath($input);
    }

    public function invalidPathProvider()
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
