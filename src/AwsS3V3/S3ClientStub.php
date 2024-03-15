<?php

declare(strict_types=1);

namespace League\Flysystem\AwsS3V3;

use Aws\Command;
use Aws\CommandInterface;
use Aws\ResultInterface;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3ClientInterface;
use Aws\S3\S3ClientTrait;
use GuzzleHttp\Psr7\Response;

use Throwable;

use function GuzzleHttp\Promise\promise_for;

/**
 * @codeCoverageIgnore
 */
class S3ClientStub implements S3ClientInterface
{
    use S3ClientTrait;

    /**
     * @var S3ClientInterface
     */
    private $actualClient;

    /**
     * @var S3Exception[]
     */
    private $stagedExceptions = [];

    /**
     * @var ResultInterface[]
     */
    private $stagedResult = [];

    /**
     * @var Throwable|null
     */
    private $exceptionForUpload = null;

    public function __construct(S3ClientInterface $client)
    {
        return $this->actualClient = $client;
    }

    public function throwDuringUpload(Throwable $throwable): void
    {
        $this->exceptionForUpload = $throwable;
    }

    public function upload($bucket, $key, $body, $acl = 'private', array $options = [])
    {
        if ($this->exceptionForUpload instanceof Throwable) {
            $throwable = $this->exceptionForUpload;
            $this->exceptionForUpload = null;
            throw $throwable;
        }

        return $this->actualClient->upload($bucket, $key, $body, $acl, $options);
    }

    public function failOnNextCopy(): void
    {
        $this->throwExceptionWhenExecutingCommand('CopyObject');
    }

    public function throwExceptionWhenExecutingCommand(string $commandName, ?S3Exception $exception = null): void
    {
        $this->stagedExceptions[$commandName] = $exception ?? new S3Exception($commandName, new Command($commandName));
    }

    public function throw500ExceptionWhenExecutingCommand(string $commandName): void
    {
        $response = new Response(500);
        $exception = new S3Exception($commandName, new Command($commandName), compact('response'));

        $this->throwExceptionWhenExecutingCommand($commandName, $exception);
    }

    public function stageResultForCommand(string $commandName, ResultInterface $result): void
    {
        $this->stagedResult[$commandName] = $result;
    }

    public function execute(CommandInterface $command)
    {
        return $this->executeAsync($command)->wait();
    }

    public function getCommand($name, array $args = [])
    {
        return $this->actualClient->getCommand($name, $args);
    }

    public function getHandlerList()
    {
        return $this->actualClient->getHandlerList();
    }

    public function getIterator($name, array $args = [])
    {
        return $this->actualClient->getIterator($name, $args);
    }

    public function __call($name, array $arguments)
    {
        return $this->actualClient->__call($name, $arguments);
    }

    public function executeAsync(CommandInterface $command)
    {
        $name = $command->getName();

        if (array_key_exists($name, $this->stagedExceptions)) {
            $exception = $this->stagedExceptions[$name];
            unset($this->stagedExceptions[$name]);
            throw $exception;
        }

        if (array_key_exists($name, $this->stagedResult)) {
            $result = $this->stagedResult[$name];
            unset($this->stagedResult[$name]);

            return promise_for($result);
        }

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
}
