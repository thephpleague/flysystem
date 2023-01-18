<?php

declare(strict_types=1);

namespace League\Flysystem\GoogleCloudStorage;

use Google\Cloud\Storage\StorageObject;

class UniformBucketLevelAccessVisibility implements VisibilityHandler
{
    public const NO_PREDEFINED_VISIBILITY = 'noPredefinedVisibility';

    public function setVisibility(StorageObject $object, string $visibility): void
    {
        // noop
    }

    public function determineVisibility(StorageObject $object): string
    {
        return self::NO_PREDEFINED_VISIBILITY;
    }

    public function visibilityToPredefinedAcl(string $visibility): string
    {
        return self::NO_PREDEFINED_VISIBILITY;
    }
}
