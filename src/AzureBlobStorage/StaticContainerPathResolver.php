<?php

declare(strict_types=1);

namespace League\Flysystem\AzureBlobStorage;

class StaticContainerPathResolver implements PathResolverInterface
{
    private $container;

    public function __construct(string $container)
    {
        $this->container = $container;
    }

    public function resolve(string $path): Resolved
    {
        return new Resolved($this->container, $path);
    }
}
