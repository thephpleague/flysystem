<?php

use League\Flysystem\PluginInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;

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

class FsPlugin implements PluginInterface
{
    public $filesystem;

    public function getMethod()
    {
        return 'fs_plugin';
    }

    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function handle($arg = false)
    {
        return $arg;
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
    protected $manager;

    protected $filesystem;
    protected $filesystem_other;
    protected $filesystem_another;

    public function setup()
    {
        $this->filesystem = new Filesystem(Mockery::mock('League\Flysystem\AdapterInterface'));
        $this->filesystem_other = new Filesystem(Mockery::mock('League\Flysystem\AdapterInterface'));
        $this->filesystem_another = new Filesystem(Mockery::mock('League\Flysystem\AdapterInterface'));
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

    /**
     * @expectedException  \LogicException
     */
    public function testInvalidPlugin()
    {
        $this->filesystem->addPlugin(new InvalidPlugin);
        $this->filesystem->beInvalid();
    }

    public function testPluginOnMultipleFilesystems()
    {
        $plugin = new FsPlugin();

        $this->manager = new \League\Flysystem\MountManager();
        $this->manager->mountFilesystem('base', $this->filesystem);
        $this->manager->mountFilesystem('other', $this->filesystem_other);
        $this->manager->mountFilesystem('another', $this->filesystem_another);

        $this->manager->addSharedPlugin($plugin);

        $this->assertTrue($this->manager->getFilesystem('base')->hasPlugin($plugin));
        $this->assertTrue($this->manager->getFilesystem('other')->hasPlugin($plugin));
        $this->assertTrue($this->manager->getFilesystem('another')->hasPlugin($plugin));
    }
}
