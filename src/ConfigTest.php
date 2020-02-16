<?php

declare(strict_types=1);

namespace League\Flysystem;

use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @test
     */
    public function a_config_object_exposes_passed_options(): void
    {
        $config = new Config(['option' => 'value']);
        $this->assertEquals('value', $config->get('option'));
    }

    /**
     * @test
     */
    public function a_config_object_returns_a_default_value(): void
    {
        $config = new Config();

        $this->assertNull($config->get('option'));
        $this->assertEquals('default', $config->get('option', 'default'));
    }

    /**
     * @test
     */
    public function extending_a_config_with_options(): void
    {
        $config = new Config(['option' => 'value', 'first' => 1]);
        $extended = $config->extend(['option' => 'overwritten', 'second' => 2]);

        $this->assertEquals('overwritten', $extended->get('option'));
        $this->assertEquals(1, $extended->get('first'));
        $this->assertEquals(2, $extended->get('second'));
    }

    /**
     * @test
     */
    public function extending_with_defaults(): void
    {
        $config = new Config(['option' => 'set']);

        $withDefaults = $config->withDefaults(['option' => 'default', 'other' => 'default']);

        $this->assertEquals('set', $withDefaults->get('option'));
        $this->assertEquals('default', $withDefaults->get('other'));
    }
}
