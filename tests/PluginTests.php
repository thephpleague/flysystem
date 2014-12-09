<?php

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;

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

class InvalidPlugin implements PluginInterface
{
    public function getMethod()
    {
        return 'beInvalid';
    }

    public function setFilesystem(FilesystemInterface $filesystem)
    {
        // yay
    }
}

class PluginTests extends PHPUnit_Framework_TestCase
{
    protected $filesystem;

    public function setup()
    {
        $this->filesystem = new Filesystem(Mockery::mock('League\Flysystem\AdapterInterface'));
    }

    /**
     * @expectedException  \LogicException
     */
    public function testPlugin()
    {
        $this->filesystem->addPlugin(new MyPlugin());
        $this->assertEquals('result', $this->filesystem->beAwesome('result'));
        $this->filesystem->unknownPlugin();
    }

    /**
     * @expectedException  \LogicException
     */
    public function testInvalidPlugin()
    {
        $this->filesystem->addPlugin(new InvalidPlugin());
        $this->filesystem->beInvalid();
    }
}
