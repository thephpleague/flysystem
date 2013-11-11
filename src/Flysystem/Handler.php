<?php

namespace Flysystem;

abstract class Handler
{
    protected $path;
    protected $filesystem;

    public function __construct(Filesystem $filesystem = null, $path = null)
    {
        $this->path = $path;
        $this->filesystem = $filesystem;
    }

    public function isDir()
    {
        return $this->getType() === 'dir';
    }

    public function isFile()
    {
        return $this->getType() === 'file';
    }

    public function getType()
    {
        $metadata = $this->filesystem->getMetadata($this->path);

        return $metadata['type'];
    }

    public function setFilesystem(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;

        return $this;
    }

    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }
}
