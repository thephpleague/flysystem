<?php

declare(strict_types=1);

namespace League\Flysystem;

use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @test
     */
    public function a_config_object_exposes_passed_options()
    {
        $config = new Config(['option' => 'value']);
        $this->assertEquals('value', $config->get('option'));
    }

    /**
     * @test
     */
    public function a_config_object_returns_a_default_value()
    {
        $config = new Config();
        $this->assertNull($config->get('option'));
        $this->assertEquals('default', $config->get('option', 'default'));
    }

    /**
     * @test
     */
    public function config_objects_can_be_merged()
    {
        $c1 = new Config(['option' => 'value', 'first' => 1]);
        $c2 = new Config(['option' => 'overwritten', 'second' => 2]);
        $merged = Config::merge($c1, $c2);

        $this->assertEquals('overwritten', $merged->get('option'));
        $this->assertEquals(1, $merged->get('first'));
        $this->assertEquals(2, $merged->get('second'));
    }
}
