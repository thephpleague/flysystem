<?php

declare(strict_types=1);

namespace League\Flysystem\AwsS3V3;

use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemAdapterTestCase;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;

/**
 * @group aws
 */
class AwsS3V3FilesystemTest extends FilesystemAdapterTestCase
{
    /**
     * @var bool
     */
    private $shouldCleanUp = false;

    /**
     * @var string
     */
    private static $adapterPrefix = 'test-prefix';

    /**
     * @var S3ClientInterface|null
     */
    private $s3Client;

    /**
     * @var S3ClientStub
     */
    private $stubS3Client;

    public static function setUpBeforeClass(): void
    {
        static::$adapterPrefix = 'travis-ci/' . bin2hex(random_bytes(10));
    }

    protected function tearDown(): void
    {
        if ( ! $this->shouldCleanUp) {
            return;
        }

        $adapter = $this->adapter();
        /** @var StorageAttributes[] $listing */
        $listing = $adapter->listContents('', false);

        foreach ($listing as $item) {
            if ($item->isFile()) {
                $adapter->delete($item->path());
            } else {
                $adapter->deleteDirectory($item->path());
            }
        }
    }

    private function s3Client(): S3ClientInterface
    {
        if ($this->s3Client instanceof S3ClientInterface) {
            return $this->s3Client;
        }

        $key = getenv('FLYSYSTEM_AWS_S3_KEY');
        $secret = getenv('FLYSYSTEM_AWS_S3_SECRET');
        $bucket = getenv('FLYSYSTEM_AWS_S3_BUCKET');
        $region = getenv('FLYSYSTEM_AWS_S3_REGION') ?: 'eu-central-1';

        if ( ! $key || ! $secret || ! $bucket) {
            $this->markTestSkipped('No AWS credentials present for testing.');
        }

        $this->shouldCleanUp = true;
        $options = ['version' => 'latest', 'credentials' => compact('key', 'secret'), 'region' => $region];

        return $this->s3Client = new S3Client($options);
    }

    /**
     * @test
     */
    public function writing_with_a_specific_mime_type(): void
    {
        $adapter = $this->adapter();
        $adapter->write('some/path.txt', 'contents', new Config(['ContentType' => 'text/plain+special']));
        $mimeType = $adapter->mimeType('some/path.txt')->mimeType();
        $this->assertEquals('text/plain+special', $mimeType);
    }

    /**
     * @test
     */
    public function listing_contents_recursive(): void
    {
        $adapter = $this->adapter();
        $adapter->write('something/0/here.txt', 'contents', new Config());
        $adapter->write('something/1/also/here.txt', 'contents', new Config());

        $contents = iterator_to_array($adapter->listContents('', true));

        $this->assertCount(2, $contents);
        $this->assertContainsOnlyInstancesOf(FileAttributes::class, $contents);
        /** @var FileAttributes $file */
        $file = $contents[0];
        $this->assertEquals('something/0/here.txt', $file->path());
        /** @var FileAttributes $file */
        $file = $contents[1];
        $this->assertEquals('something/1/also/here.txt', $file->path());
    }

    /**
     * @test
     */
    public function failing_to_delete_while_moving(): void
    {
        $this->expectException(UnableToMoveFile::class);

        $adapter = $this->adapter();
        $adapter->write('source.txt', 'contents to be copied', new Config());
        $this->stubS3Client->throwExceptionWhenExecutingCommand('CopyObject');

        $adapter->move('source.txt', 'destination.txt', new Config());
    }

    /**
     * @test
     */
    public function failing_to_delete_a_file(): void
    {
        $this->expectException(UnableToDeleteFile::class);

        $adapter = $this->adapter();
        $this->stubS3Client->throwExceptionWhenExecutingCommand('DeleteObject');

        $adapter->delete('path.txt');
    }

    protected function createFilesystemAdapter(): FilesystemAdapter
    {
        $this->stubS3Client = new S3ClientStub($this->s3Client());
        /** @var string $bucket */
        $bucket = getenv('FLYSYSTEM_AWS_S3_BUCKET');
        $prefix = getenv('FLYSYSTEM_AWS_S3_PREFIX') ?: static::$adapterPrefix;

        return new AwsS3V3Filesystem($this->stubS3Client, $bucket, $prefix);
    }
}
