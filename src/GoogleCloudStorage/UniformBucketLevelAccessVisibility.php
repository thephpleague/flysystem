<?php
declare(strict_types=1);

namespace League\Flysystem\GoogleCloudStorage;

use Google\Cloud\Core\Exception\NotFoundException;
use Google\Cloud\Storage\Acl;
use Google\Cloud\Storage\StorageObject;
use League\Flysystem\Visibility;

class UniformBucketLevelAccessVisibility implements VisibilityHandler
{
    public const NO_PREDEFINED_VISIBILITY = 'noPredefinedVisibility';

    public function setVisibility(StorageObject $object, string $visibility): void
    {
        // noop
    }

    public function determineVisibility(StorageObject $object): string
    {
        try {
            $acl = $object->acl()->get(['entity' => 'allUsers']);
        } catch (NotFoundException $exception) {
            return Visibility::PRIVATE;
        }

        return $acl['role'] === Acl::ROLE_READER
            ? Visibility::PUBLIC
            : Visibility::PRIVATE;
    }

    public function visibilityToPredefinedAcl(string $visibility): string
    {
        return self::NO_PREDEFINED_VISIBILITY;
    }
}
