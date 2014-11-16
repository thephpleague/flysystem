<?php

namespace League\Flysystem\Stub;

use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;

class PluginStub implements PluginInterface
{
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        return $this;
    }

    public function getMethod()
    {
        return 'pluginMethod';
    }

    public function handle()
    {
        return 'handled';
    }
}
