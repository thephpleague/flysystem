<?php

declare(strict_types=1);

namespace League\Flysystem\GoogleCloudStorage;

use Google\Cloud\Storage\Connection\ConnectionInterface;
use Google\Cloud\Storage\StorageObject;
use LogicException;

/**
 * @codeCoverageIgnore
 */
class StubObject extends StorageObject
{
    /**
     * @var StorageObject
     */
    private $storageObject;

    /**
     * @var bool
     */
    private $shouldFailWhenAccessingAcl = false;

    /**
     * @var bool
     */
    private $shouldFailWhenDeleting = false;

    public function __construct(
        ConnectionInterface $connection,
        string $name,
        string $bucket,
        StorageObject $storageObject
    ) {
        parent::__construct($connection, $name, $bucket);
        $this->storageObject = $storageObject;
    }

    public function failWhenAccessingAcl(): void
    {
        $this->shouldFailWhenAccessingAcl = true;
    }

    public function acl()
    {
        if ($this->shouldFailWhenAccessingAcl) {
            $this->shouldFailWhenAccessingAcl = false;
            throw new LogicException("Something bad happened! Oh no!");
        }

        return $this->storageObject->acl();
    }

    public function failWhenDeleting(): void
    {
        $this->shouldFailWhenDeleting = true;
    }

    /**
     * @param array $options
     */
    public function delete(array $options = [])
    {
        if ($this->shouldFailWhenDeleting) {
            $this->shouldFailWhenDeleting = false;
            throw new LogicException("Oh no!");
        }

        parent::delete($options);
    }
}
