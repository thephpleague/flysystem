<?php

declare(strict_types=1);

namespace League\Flysystem\AzureBlobStorage;

class StaticContainerPathResolver implements PathResolverInterface
{
    /** @var string */
    private $container;

    public function __construct(string $container)
    {
        $this->container = $container;
    }

    public function resolve(string $path): Path
    {
        return new Path($this->container, $path);
    }
}

