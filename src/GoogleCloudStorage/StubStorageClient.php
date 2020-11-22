<?php

declare(strict_types=1);

namespace League\Flysystem\GoogleCloudStorage;

use Google\Cloud\Storage\Connection\ConnectionInterface;
use Google\Cloud\Storage\StorageClient;

class StubStorageClient extends StorageClient
{
    /**
     * @var string|null
     */
    protected $projectId;

    public function connection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function projectId(): ?string
    {
        return $this->projectId;
    }
}
