<?php

declare(strict_types=1);

namespace League\Flysystem\AwsS3V3;

use Aws\Command;
use Aws\CommandInterface;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3ClientInterface;

/**
 * @codeCoverageIgnore
 */
class S3ClientStub implements S3ClientInterface
{
    /**
     * @var S3ClientInterface
     */
    private $actualClient;

    /**
     * @var S3Exception[]
     */
    private $stagedExceptions = [];

    public function __construct(S3ClientInterface $actualClient)
    {
        $this->actualClient = $actualClient;
    }

    public function __call($name, array $arguments)
    {
        return $this->actualClient->__call($name, $arguments);
    }

    public function getCommand($name, array $args = [])
    {
        return $this->actualClient->getCommand($name, $args);
    }

    public function execute(CommandInterface $command)
    {
        if (array_key_exists($name = $command->getName(), $this->stagedExceptions)) {
            $exception = $this->stagedExceptions[$name];
            unset($this->stagedExceptions[$name]);
            throw $exception;
        }

        return $this->actualClient->execute($command);
    }

    public function throwExceptionWhenExecutingCommand(string $commandName): void
    {
        $this->stagedExceptions[$commandName] = new S3Exception($commandName, new Command($commandName));
    }

    public function executeAsync(CommandInterface $command)
    {
        return $this->actualClient->executeAsync($command);
    }

    public function getCredentials()
    {
        return $this->actualClient->getCredentials();
    }

    public function getRegion()
    {
        return $this->actualClient->getRegion();
    }

    public function getEndpoint()
    {
        return $this->actualClient->getEndpoint();
    }

    public function getApi()
    {
        return $this->actualClient->getApi();
    }

    public function getConfig($option = null)
    {
        return $this->actualClient->getConfig($option);
    }

    public function getHandlerList()
    {
        return $this->actualClient->getHandlerList();
    }

    public function getIterator($name, array $args = [])
    {
        return $this->actualClient->getIterator($name, $args);
    }

    public function getPaginator($name, array $args = [])
    {
        return $this->actualClient->getPaginator($name, $args);
    }

    public function waitUntil($name, array $args = [])
    {
        $this->actualClient->waitUntil($name, $args);
    }

    public function getWaiter($name, array $args = [])
    {
        return $this->actualClient->getWaiter($name, $args);
    }

    public function createPresignedRequest(CommandInterface $command, $expires, array $options = [])
    {
        return $this->actualClient->createPresignedRequest($command, $expires, $options);
    }

    public function getObjectUrl($bucket, $key)
    {
        return $this->actualClient->getObjectUrl($bucket, $key);
    }

    public function doesBucketExist($bucket)
    {
        return $this->actualClient->doesBucketExist($bucket);
    }

    public function doesObjectExist($bucket, $key, array $options = [])
    {
        return $this->actualClient->doesObjectExist($bucket, $key, $options);
    }

    public function registerStreamWrapper(): void
    {
        $this->actualClient->registerStreamWrapper();
    }

    public function deleteMatchingObjects($bucket, $prefix = '', $regex = '', array $options = [])
    {
        return $this->actualClient->deleteMatchingObjects($bucket, $prefix, $regex, $options);
    }

    public function deleteMatchingObjectsAsync($bucket, $prefix = '', $regex = '', array $options = [])
    {
        return $this->actualClient->deleteMatchingObjectsAsync($bucket, $prefix, $regex, $options);
    }

    public function upload($bucket, $key, $body, $acl = 'private', array $options = [])
    {
        return $this->actualClient->upload($bucket, $key, $body, $acl, $options);
    }

    public function uploadAsync($bucket, $key, $body, $acl = 'private', array $options = [])
    {
        return $this->actualClient->uploadAsync($bucket, $key, $body, $acl, $options);
    }

    public function copy($fromBucket, $fromKey, $destBucket, $destKey, $acl = 'private', array $options = [])
    {
        return $this->actualClient->copy($fromBucket, $fromKey, $destBucket, $destKey, $acl, $options);
    }

    public function copyAsync($fromBucket, $fromKey, $destBucket, $destKey, $acl = 'private', array $options = [])
    {
        return $this->actualClient->copyAsync($fromBucket, $fromKey, $destBucket, $destKey, $acl, $options);
    }

    public function uploadDirectory($directory, $bucket, $keyPrefix = null, array $options = [])
    {
        return $this->actualClient->uploadDirectory($directory, $bucket, $keyPrefix, $options);
    }

    public function uploadDirectoryAsync($directory, $bucket, $keyPrefix = null, array $options = [])
    {
        return $this->actualClient->uploadDirectoryAsync($directory, $bucket, $keyPrefix, $options);
    }

    public function downloadBucket($directory, $bucket, $keyPrefix = '', array $options = [])
    {
        return $this->actualClient->downloadBucket($directory, $bucket, $keyPrefix, $options);
    }

    public function downloadBucketAsync($directory, $bucket, $keyPrefix = '', array $options = [])
    {
        return $this->actualClient->downloadBucketAsync($directory, $bucket, $keyPrefix, $options);
    }

    public function determineBucketRegion($bucketName)
    {
        return $this->actualClient->determineBucketRegion($bucketName);
    }

    public function determineBucketRegionAsync($bucketName)
    {
        return $this->actualClient->determineBucketRegionAsync($bucketName);
    }
}
