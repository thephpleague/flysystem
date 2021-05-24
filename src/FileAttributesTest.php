<?php

declare(strict_types=1);

namespace League\Flysystem;

use Generator;
use PHPUnit\Framework\TestCase;

use RuntimeException;

use function time;

/**
 * @group core
 */
class FileAttributesTest extends TestCase
{
    /**
     * @test
     */
    public function exposing_some_values(): void
    {
        $attrs = new FileAttributes('path.txt');
        $this->assertFalse($attrs->isDir());
        $this->assertTrue($attrs->isFile());
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
    public function exposing_all_values(): void
    {
        $attrs = new FileAttributes('path.txt', 1234, Visibility::PRIVATE, $now = time(), 'plain/text', ['key' => 'value']);
        $this->assertEquals('path.txt', $attrs->path());
        $this->assertEquals(StorageAttributes::TYPE_FILE, $attrs->type());
        $this->assertEquals(Visibility::PRIVATE, $attrs->visibility());
        $this->assertEquals(1234, $attrs->fileSize());
        $this->assertEquals($now, $attrs->lastModified());
        $this->assertEquals('plain/text', $attrs->mimeType());
        $this->assertEquals(['key' => 'value'], $attrs->extraMetadata());
    }

    /**
     * @test
     */
    public function implements_array_access(): void
    {
        $attrs = new FileAttributes('path.txt', 1234, Visibility::PRIVATE, $now = time(), 'plain/text', ['key' => 'value']);
        $this->assertEquals('path.txt', $attrs['path']);
        $this->assertTrue(isset($attrs['path']));
        $this->assertEquals(StorageAttributes::TYPE_FILE, $attrs['type']);
        $this->assertEquals(Visibility::PRIVATE, $attrs['visibility']);
        $this->assertEquals(1234, $attrs['file_size']);
        $this->assertEquals($now, $attrs['last_modified']);
        $this->assertEquals('plain/text', $attrs['mimeType']);
        $this->assertEquals(['key' => 'value'], $attrs['extra_metadata']);
    }

    /**
     * @test
     */
    public function properties_can_not_be_set(): void
    {
        $this->expectException(RuntimeException::class);
        $attrs = new FileAttributes('path.txt');
        $attrs['visibility'] = Visibility::PUBLIC;
    }

    /**
     * @test
     */
    public function properties_can_not_be_unset(): void
    {
        $this->expectException(RuntimeException::class);
        $attrs = new FileAttributes('path.txt');
        unset($attrs['visibility']);
    }

    /**
     * @dataProvider data_provider_for_json_transformation
     * @test
     */
    public function json_transformations(FileAttributes $attributes): void
    {
        $payload = $attributes->jsonSerialize();
        $newAttributes = FileAttributes::fromArray($payload);
        $this->assertEquals($attributes, $newAttributes);
    }

    public function data_provider_for_json_transformation(): Generator
    {
        yield [new FileAttributes('path.txt', 1234, Visibility::PRIVATE, $now = time(), 'plain/text', ['key' => 'value'])];
        yield [new FileAttributes('another.txt')];
    }
}
