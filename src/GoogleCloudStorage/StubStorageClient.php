<?php

declare(strict_types=1);

namespace League\Flysystem\GoogleCloudStorage;

use Google\Cloud\Storage\StorageClient;
use function in_array;

class StubStorageClient extends StorageClient
{
    private ?StubRiggedBucket $riggedBucket = null;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * @var string|null
     */
    protected $projectId;

    public function bucket($name, $userProject = false)
    {
        $knownBuckets = ['flysystem', 'no-acl-bucket-for-ci'];
        $isKnownBucket = in_array($name, $knownBuckets);

        if ($isKnownBucket && ! $this->riggedBucket) {
            $this->riggedBucket = new StubRiggedBucket($this->connection, $name, [
                'requesterProjectId' => $this->projectId,
            ]);
        }

        return $isKnownBucket ? $this->riggedBucket : parent::bucket($name, $userProject);
    }
}
