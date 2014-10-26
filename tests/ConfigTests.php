<?php

namespace League\Flysystem\Tests;

use League\Flysystem\Config;
use PHPUnit_Framework_TestCase;

class ConfigTests extends PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        $config = new Config();
        $this->assertNull($config->get('setting'));
        $config->set('setting', 'value');
        $this->assertEquals('value', $config->get('setting'));
        $fallback = new Config(array('fallback_setting' => 'fallback_value'));
        $config->setFallback($fallback);
        $this->assertEquals('fallback_value', $config->get('fallback_setting'));
    }
}
