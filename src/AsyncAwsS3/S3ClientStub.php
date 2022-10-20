<?php

declare(strict_types=1);

namespace League\Flysystem\AsyncAwsS3;

use AsyncAws\Core\Exception\Exception;
use AsyncAws\Core\Exception\Http\NetworkException;
use AsyncAws\Core\Result;
use AsyncAws\S3\Input\CopyObjectRequest;
use AsyncAws\S3\Input\DeleteObjectRequest;
use AsyncAws\S3\Input\DeleteObjectsRequest;
use AsyncAws\S3\Input\GetObjectAclRequest;
use AsyncAws\S3\Input\GetObjectRequest;
use AsyncAws\S3\Input\HeadObjectRequest;
use AsyncAws\S3\Input\ListObjectsV2Request;
use AsyncAws\S3\Input\PutObjectAclRequest;
use AsyncAws\S3\Input\PutObjectRequest;
use AsyncAws\S3\Result\CopyObjectOutput;
use AsyncAws\S3\Result\DeleteObjectOutput;
use AsyncAws\S3\Result\DeleteObjectsOutput;
use AsyncAws\S3\Result\GetObjectAclOutput;
use AsyncAws\S3\Result\GetObjectOutput;
use AsyncAws\S3\Result\HeadObjectOutput;
use AsyncAws\S3\Result\ListObjectsV2Output;
use AsyncAws\S3\Result\ObjectExistsWaiter;
use AsyncAws\S3\Result\PutObjectAclOutput;
use AsyncAws\S3\Result\PutObjectOutput;
use AsyncAws\S3\S3Client;
use AsyncAws\SimpleS3\SimpleS3Client;
use DateTimeImmutable;
use Symfony\Component\HttpClient\MockHttpClient;

/**
 * @codeCoverageIgnore
 */
class S3ClientStub extends SimpleS3Client
{
    /**
     * @var S3Client
     */
    private $actualClient;

    /**
     * @var Exception[]
     */
    private $stagedExceptions = [];

    /**
     * @var Result[]
     */
    private $stagedResult = [];

    public function __construct(SimpleS3Client $client)
    {
        $this->actualClient = $client;
        parent::__construct([], null, new MockHttpClient());
    }

    public function throwExceptionWhenExecutingCommand(string $commandName, Exception $exception = null): void
    {
        $this->stagedExceptions[$commandName] = $exception ?: new NetworkException();
    }

    public function stageResultForCommand(string $commandName, Result $result): void
    {
        $this->stagedResult[$commandName] = $result;
    }

    private function getStagedResult(string $name): ?Result
    {
        if (array_key_exists($name, $this->stagedExceptions)) {
            $exception = $this->stagedExceptions[$name];
            unset($this->stagedExceptions[$name]);

            throw $exception;
        }

        if (array_key_exists($name, $this->stagedResult)) {
            $result = $this->stagedResult[$name];
            unset($this->stagedResult[$name]);

            return $result;
        }

        return null;
    }

    /**
     * @param array|CopyObjectRequest $input
     */
    public function copyObject($input): CopyObjectOutput
    {
        // @phpstan-ignore-next-line
        return $this->getStagedResult('CopyObject') ?? $this->actualClient->copyObject($input);
    }

    /**
     * @param array|DeleteObjectRequest $input
     */
    public function deleteObject($input): DeleteObjectOutput
    {
        // @phpstan-ignore-next-line
        return $this->getStagedResult('DeleteObject') ?? $this->actualClient->deleteObject($input);
    }

    /**
     * @param array|HeadObjectRequest $input
     */
    public function headObject($input): HeadObjectOutput
    {
        // @phpstan-ignore-next-line
        return $this->getStagedResult('HeadObject') ?? $this->actualClient->headObject($input);
    }

    /**
     * @param array|HeadObjectRequest $input
     */
    public function objectExists($input): ObjectExistsWaiter
    {
        // @phpstan-ignore-next-line
        return $this->getStagedResult('ObjectExists') ?? $this->actualClient->objectExists($input);
    }

    /**
     * @param array|ListObjectsV2Request $input
     */
    public function listObjectsV2($input): ListObjectsV2Output
    {
        // @phpstan-ignore-next-line
        return $this->getStagedResult('ListObjectsV2') ?? $this->actualClient->listObjectsV2($input);
    }

    /**
     * @param array|DeleteObjectsRequest $input
     */
    public function deleteObjects($input): DeleteObjectsOutput
    {
        // @phpstan-ignore-next-line
        return $this->getStagedResult('DeleteObjects') ?? $this->actualClient->deleteObjects($input);
    }

    /**
     * @param array|GetObjectAclRequest $input
     */
    public function getObjectAcl($input): GetObjectAclOutput
    {
        // @phpstan-ignore-next-line
        return $this->getStagedResult('GetObjectAcl') ?? $this->actualClient->getObjectAcl($input);
    }

    /**
     * @param array|PutObjectAclRequest $input
     */
    public function putObjectAcl($input): PutObjectAclOutput
    {
        // @phpstan-ignore-next-line
        return $this->getStagedResult('PutObjectAcl') ?? $this->actualClient->putObjectAcl($input);
    }

    /**
     * @param array|PutObjectRequest $input
     */
    public function putObject($input): PutObjectOutput
    {
        // @phpstan-ignore-next-line
        return $this->getStagedResult('PutObject') ?? $this->actualClient->putObject($input);
    }

    /**
     * @param array|GetObjectRequest $input
     */
    public function getObject($input): GetObjectOutput
    {
        // @phpstan-ignore-next-line
        return $this->getStagedResult('GetObject') ?? $this->actualClient->getObject($input);
    }

    public function getUrl(string $bucket, string $key): string
    {
        return $this->actualClient->getUrl($bucket, $key);
    }

    public function getPresignedUrl(string $bucket, string $key, ?DateTimeImmutable $expires = null): string
    {
        return $this->actualClient->getPresignedUrl($bucket, $key, $expires);
    }
}
