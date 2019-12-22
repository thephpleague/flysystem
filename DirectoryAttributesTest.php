<?php

declare(strict_types=1);

namespace League\Flysystem;

use PHPUnit\Framework\TestCase;

class DirectoryAttributesTest extends TestCase
{
    /**
     * @test
     */
    public function exposing_some_values()
    {
        $attrs = new DirectoryAttributes('some/path');
        $this->assertEquals(StorageAttributes::TYPE_DIRECTORY, $attrs->type());
        $this->assertEquals('some/path', $attrs->path());
        $this->assertNull($attrs->visibility());
    }
    /**
     * @test
     */
    public function exposing_visibility()
    {
        $attrs = new DirectoryAttributes('some/path', Visibility::PRIVATE);
        $this->assertEquals(Visibility::PRIVATE, $attrs->visibility());
    }
}
