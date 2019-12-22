<?php

declare(strict_types=1);

namespace League\Flysystem;

use PHPUnit\Framework\TestCase;

use function time;

class FileAttributesTest extends TestCase
{
    /**
     * @test
     */
    public function exposing_some_values()
    {
        $attrs = new FileAttributes('path.txt');
        $this->assertEquals('path.txt', $attrs->path());
        $this->assertEquals(StorageAttributes::TYPE_FILE, $attrs->type());
        $this->assertNull($attrs->visibility());
        $this->assertNull($attrs->fileSize());
        $this->assertNull($attrs->mimeType());
        $this->assertNull($attrs->lastModified());
    }
    /**
     * @test
     */
    public function exposing_all_values()
    {
        $attrs = new FileAttributes('path.txt', 1234, Visibility::PRIVATE, $now = time(), 'plain/text');
        $this->assertEquals('path.txt', $attrs->path());
        $this->assertEquals(StorageAttributes::TYPE_FILE, $attrs->type());
        $this->assertEquals(Visibility::PRIVATE, $attrs->visibility());
        $this->assertEquals(1234, $attrs->fileSize());
        $this->assertEquals($now, $attrs->lastModified());
        $this->assertEquals('plain/text', $attrs->mimeType());
    }
}
