<?php

declare(strict_types=1);

namespace League\Flysystem\AzureBlobStorage;

use function count;
use function str_replace;
use const DIRECTORY_SEPARATOR;

class FirstDirectoryAsContainerPathResolver implements PathResolverInterface
{
    private $container;

    public function __construct(string $container)
    {
        $this->container = $container;
    }

    public function resolve(string $path): Resolved
    {
        $directories = explode(DIRECTORY_SEPARATOR, $path);

        if (count($directories) <= 1) {
            return new Resolved($this->container, $path);
        }

        return new Resolved($directories[0], str_replace($directories[0].DIRECTORY_SEPARATOR, '', $path));
    }
}
