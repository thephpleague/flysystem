<?php

declare(strict_types=1);

namespace League\Flysystem\AzureBlobStorage;

class Resolved
{
    /** @var string */
    private $container;

    /** @var string */
    private $path;

    public function __construct(string $container, string $path)
    {
        $this->container = $container;
        $this->path = $path;
    }

    public function getContainer(): string
    {
        return $this->container;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
