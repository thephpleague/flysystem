<?php

declare(strict_types=1);

namespace League\Flysystem;

use PHPUnit\Framework\TestCase;

class PathPrefixerTest extends TestCase
{
    /**
     * @test
     */
    public function path_prefixing_with_a_prefix()
    {
        $prefixer = new PathPrefixer('prefix');
        $prefixedPath = $prefixer->prefixPath('some/path.txt');
        $this->assertEquals('prefix/some/path.txt', $prefixedPath);
    }

    /**
     * @test
     */
    public function path_stripping_with_a_prefix()
    {
        $prefixer = new PathPrefixer('prefix');
        $strippedPath = $prefixer->stripPrefix('prefix/some/path.txt');
        $this->assertEquals('some/path.txt', $strippedPath);
    }

    /**
     * @test
     */
    public function path_stripping_is_reversable()
    {
        $prefixer = new PathPrefixer('prefix');
        $strippedPath = $prefixer->stripPrefix('prefix/some/path.txt');
        $this->assertEquals('prefix/some/path.txt', $prefixer->prefixPath($strippedPath));
        $prefixedPath = $prefixer->prefixPath('some/path.txt');
        $this->assertEquals('some/path.txt', $prefixer->stripPrefix($prefixedPath));
    }
}
