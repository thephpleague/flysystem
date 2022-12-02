<?php
declare(strict_types=1);

namespace League\Flysystem\GoogleCloudStorage;

class GoogleCloudStorageAdapterWithoutAclTest extends GoogleCloudStorageAdapterTest
{
    protected static function visibilityHandler(): VisibilityHandler
    {
        return new UniformBucketLevelAccessVisibility();
    }

    protected static function bucketName(): string|array|false
    {
        return 'no-acl-bucket-for-ci';
    }
}
