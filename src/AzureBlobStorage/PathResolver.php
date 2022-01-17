<?php

declare(strict_types=1);

namespace League\Flysystem\AzureBlobStorage;

interface PathResolver
{
    public function resolve(string $path): Path;
}
