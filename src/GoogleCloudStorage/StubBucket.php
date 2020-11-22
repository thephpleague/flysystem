<?php

declare(strict_types=1);

namespace League\Flysystem\GoogleCloudStorage;

use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\Connection\ConnectionInterface;
use LogicException;

/**
 * @codeCoverageIgnore
 */
class StubBucket extends Bucket
{
    /**
     * @var ConnectionInterface
     */
    private $theConnection;

    /**
     * @var array<string, StubObject>
     */
    private $stubbedObjects;

    /**
     * @var bool
     */
    private $shouldFailOnUpload = false;

    public function __construct(ConnectionInterface $connection, $name, array $info = [])
    {
        parent::__construct($connection, $name, $info);
        $this->theConnection = $connection;
    }

    public function withObject(string $path): StubObject
    {
        return $this->stubbedObjects[$path] = new StubObject($this->theConnection, $path, '', parent::object($path));
    }

    public function object($name, array $options = [])
    {
        $object = $this->stubbedObjects[$name] ?? parent::object($name, $options);
        unset($this->stubbedObjects[$name]);

        return $object;
    }

    public function failOnUpload(): void
    {
        $this->shouldFailOnUpload = true;
    }

    public function upload($data, array $options = [])
    {
        if ($this->shouldFailOnUpload) {
            $this->shouldFailOnUpload = false;
            throw new LogicException("Oh no!");
        }

        return parent::upload($data, $options);
    }
}
