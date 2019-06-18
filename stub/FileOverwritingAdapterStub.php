<?php

namespace League\Flysystem\Stub;

use League\Flysystem\Adapter\CanOverwriteFiles;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

/**
 * @codeCoverageIgnore
 */
class FileOverwritingAdapterStub implements AdapterInterface, CanOverwriteFiles
{
    public $writtenPath = '';
    public $writtenContents = '';

    public function write($path, $contents, Config $config)
    {
        $this->writtenPath = $path;
        $this->writtenContents = $contents;

        return true;
    }

    public function writeStream($path, $resource, Config $config)
    {
        $this->writtenPath = $path;
        $this->writtenContents = stream_get_contents($resource);

        return true;
    }

    public function update($path, $contents, Config $config)
    {
    }

    public function updateStream($path, $resource, Config $config)
    {
    }

    public function rename($path, $newpath)
    {
    }

    public function copy($path, $newpath)
    {
    }

    public function delete($path)
    {
    }

    public function deleteDir($dirname)
    {
    }

    public function createDir($dirname, Config $config)
    {
    }

    public function setVisibility($path, $visibility)
    {
    }

    public function has($path)
    {
    }

    public function read($path)
    {
    }

    public function readStream($path)
    {
    }

    public function listContents($directory = '', $recursive = false)
    {
    }

    public function getMetadata($path)
    {
    }

    public function getSize($path)
    {
    }

    public function getMimetype($path)
    {
    }

    public function getTimestamp($path)
    {
    }

    public function getVisibility($path)
    {
    }
}
