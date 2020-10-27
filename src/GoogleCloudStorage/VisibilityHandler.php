<?php

declare(strict_types=1);

namespace League\Flysystem\GoogleCloudStorage;

use Google\Cloud\Storage\StorageObject;

interface VisibilityHandler
{
    public function setVisibility(StorageObject $object, string $visibility): void;
    public function determineVisibility(StorageObject $object): string;
    public function visibilityToPredefinedAcl(string $visibility): string;
}
