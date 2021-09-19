<?php

namespace League\Flysystem\AzureBlobStorage;

class Path
{
    private $container;
    private $path;

    public function __construct($container, $path)
    {
        $this->container = $container;
        $this->path = $path;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function getPath()
    {
        return $this->path;
    }
}

