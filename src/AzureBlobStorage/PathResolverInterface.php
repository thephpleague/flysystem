<?php

declare(strict_types=1);

namespace League\Flysystem\AzureBlobStorage;

interface PathResolverInterface
{
    public function resolve(string $path): Path;
}

