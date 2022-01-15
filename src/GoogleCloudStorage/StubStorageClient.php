<?php

declare(strict_types=1);

namespace League\Flysystem\GoogleCloudStorage;

use Google\Cloud\Storage\StorageClient;

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
        if ($name === 'flysystem' && ! $this->riggedBucket) {
            $this->riggedBucket = new StubRiggedBucket($this->connection, 'flysystem', [
                'requesterProjectId' => $this->projectId,
            ]);
        }

        return $name === 'flysystem' ? $this->riggedBucket : parent::bucket($name, $userProject);
    }
}
