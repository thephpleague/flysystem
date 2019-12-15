<?php

declare(strict_types=1);

namespace League\Flysystem;

use PHPUnit\Framework\TestCase;

class VisibilityTest extends TestCase
{
    /**
     * @test
     */
    public function it_recognizes_valid_input()
    {
        $this->assertTrue(Visibility::exists('public'));
        $this->assertTrue(Visibility::exists('private'));
        $this->assertFalse(Visibility::exists('something-else'));
    }
}
