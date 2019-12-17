<?php

declare(strict_types=1);

namespace League\Flysystem;

use InvalidArgumentException;
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
        $this->assertTrue(Visibility::exists('unknown'));
        $this->assertFalse(Visibility::exists('something-else'));
    }

    /**
     * @test
     */
    public function it_can_guard_against_invalid_visibility()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Incorrect visibility: invalid');
        Visibility::guardAgainstInvalidVisibility('invalid');
    }

    /**
     * @test
     */
    public function detects_valid_input_when_guarding_against_invalid_visibility()
    {
        Visibility::guardAgainstInvalidVisibility('public');
        $this->assertTrue(true);
    }
}
