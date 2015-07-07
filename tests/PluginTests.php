<?php

use League\Flysystem\File;
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

class AuthorizePlugin implements PluginInterface
{
    public function getMethod()
    {
        return 'authorize';
    }

    public function setFilesystem(FilesystemInterface $filesystem)
    {
        // yay
    }

    public function handle($path)
    {
        return $path !== 'bad';
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

    public function testMagicCall()
    {
        $this->filesystem->addPlugin(new AuthorizePlugin());

        $badFile = $this->filesystem->get('bad', new File());
        $this->assertFalse($badFile->authorize());

        $goodFile = $this->filesystem->get('good', new File());
        $this->assertTrue($goodFile->authorize());
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testBadMagicCall()
    {
        $file = $this->filesystem->get('foo', new File());
        $file->nonExistentMethod();
    }
}
