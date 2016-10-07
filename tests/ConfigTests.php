<?php

use League\Flysystem\Config;

class ConfigTests extends PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        $config = new Config();
        $this->assertFalse($config->has('setting'));
        $this->assertNull($config->get('setting'));
        $config->set('setting', 'value');
        $this->assertEquals('value', $config->get('setting'));
        $fallback = new Config(['fallback_setting' => 'fallback_value']);
        $config->setFallback($fallback);
        $this->assertEquals('fallback_value', $config->get('fallback_setting'));
    }

    public function testFallingBackWhenCallingHas()
    {
        $config = new Config();
        $fallback = new Config(['setting_name' => true]);
        $config->setFallback($fallback);

        $this->assertTrue($config->has('setting_name'));
    }
}
