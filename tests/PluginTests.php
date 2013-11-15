<?php

use Flysystem\PluginInterface;
use Flysystem\Filesystem;
use Flysystem\FilesystemInterface;

class MyPlugin implements PluginInterface
{
    public function getMethod()
    {
        return 'beAwesome';
    }

    public function setFilesystem(FilesystemInterface $filesystem)
    {
        // yay
    }

    public function handle($argument = false)
    {
        return $argument;
    }
}

class PluginTests extends PHPUnit_Framework_TestCase
{
    protected $filesystem;

    public function setup()
    {
        $this->filesystem = new Filesystem(Mockery::mock('Flysystem\AdapterInterface'));
    }

    /**
     * @expectedException  \LogicException
     */
    public function testPlugin()
    {
        $this->filesystem->addPlugin(new MyPlugin);
        $this->assertEquals('result', $this->filesystem->beAwesome('result'));
        $this->filesystem->unknownPlugin();
    }
}