<?php

declare(strict_types=1);

namespace League\Flysystem\AwsS3V3;

use Aws\Result;
use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;
use Exception;
use Generator;
use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;
use RuntimeException;

use function getenv;

/**
 * @group aws
 */
class AwsS3V3AdapterTest extends FilesystemAdapterTestCase
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
    private static $s3Client;

    /**
     * @var S3ClientStub
     */
    private static $stubS3Client;

    public static function setUpBeforeClass(): void
    {
        static::$adapterPrefix = getenv('FLYSYSTEM_AWS_S3_PREFIX') ?: 'ci/' . bin2hex(random_bytes(10));
    }

    protected function tearDown(): void
    {
        if ( ! $this->shouldCleanUp) {
            return;
        }

        $adapter = $this->adapter();
        $adapter->deleteDirectory('/');
        /** @var StorageAttributes[] $listing */
        $listing = $adapter->listContents('', false);

        foreach ($listing as $item) {
            if ($item->isFile()) {
                $adapter->delete($item->path());
            } else {
                $adapter->deleteDirectory($item->path());
            }
        }

        self::$adapter = null;
    }

    private static function s3Client(): S3ClientInterface
    {
        if (static::$s3Client instanceof S3ClientInterface) {
            return static::$s3Client;
        }

        $key = getenv('FLYSYSTEM_AWS_S3_KEY');
        $secret = getenv('FLYSYSTEM_AWS_S3_SECRET');
        $bucket = getenv('FLYSYSTEM_AWS_S3_BUCKET');
        $region = getenv('FLYSYSTEM_AWS_S3_REGION') ?: 'eu-central-1';

        if ( ! $key || ! $secret || ! $bucket) {
            self::markTestSkipped('No AWS credentials present for testing.');
        }

        $options = ['version' => 'latest', 'credentials' => compact('key', 'secret'), 'region' => $region];

        return static::$s3Client = new S3Client($options);
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
        $adapter = $this->adapter();
        $adapter->write('source.txt', 'contents to be copied', new Config());
        static::$stubS3Client->failOnNextCopy();

        $this->expectException(UnableToMoveFile::class);

        $adapter->move('source.txt', 'destination.txt', new Config());
    }

    /**
     * @test
     */
    public function failing_to_write_a_file(): void
    {
        $adapter = $this->adapter();
        static::$stubS3Client->throwDuringUpload(new RuntimeException('Oh no'));

        $this->expectException(UnableToWriteFile::class);

        $adapter->write('path.txt', 'contents', new Config());
    }

    /**
     * @test
     */
    public function failing_to_delete_a_file(): void
    {
        $adapter = $this->adapter();
        static::$stubS3Client->throwExceptionWhenExecutingCommand('DeleteObject');

        $this->expectException(UnableToDeleteFile::class);

        $adapter->delete('path.txt');
    }

    /**
     * @test
     */
    public function fetching_unknown_mime_type_of_a_file(): void
    {
        $this->adapter();
        $result = new Result([
            'Key' => static::$adapterPrefix . '/unknown-mime-type.md5',
        ]);
        static::$stubS3Client->stageResultForCommand('HeadObject', $result);

        parent::fetching_unknown_mime_type_of_a_file();
    }

    /**
     * @test
     * @dataProvider dpFailingMetadataGetters
     */
    public function failing_to_retrieve_metadata(Exception $exception, string $getterName): void
    {
        $adapter = $this->adapter();
        $result = new Result([
             'Key' => static::$adapterPrefix . '/filename.txt',
        ]);
        static::$stubS3Client->stageResultForCommand('HeadObject', $result);

        $this->expectExceptionObject($exception);

        $adapter->{$getterName}('filename.txt');
    }

    public function dpFailingMetadataGetters(): iterable
    {
        yield "mimeType" => [UnableToRetrieveMetadata::mimeType('filename.txt'), 'mimeType'];
        yield "lastModified" => [UnableToRetrieveMetadata::lastModified('filename.txt'), 'lastModified'];
        yield "fileSize" => [UnableToRetrieveMetadata::fileSize('filename.txt'), 'fileSize'];
    }

    /**
     * @test
     */
    public function failing_to_check_for_file_existence(): void
    {
        $adapter = $this->adapter();

        static::$stubS3Client->throw500ExceptionWhenExecutingCommand('HeadObject');

        $this->expectException(UnableToCheckExistence::class);

        $adapter->fileExists('something-that-does-exist.txt');
    }

    /**
     * @test
     * @dataProvider casesWhereHttpStreamingInfluencesSeekability
     */
    public function streaming_reads_are_not_seekable_and_non_streaming_are(bool $streaming, bool $seekable): void
    {
        if (getenv('COMPOSER_OPTS') === '--prefer-lowest') {
            $this->markTestSkipped('The SDK does not support streaming in low versions.');
        }

        $adapter = $this->useAdapter($this->createFilesystemAdapter($streaming));
        $this->givenWeHaveAnExistingFile('path.txt');

        $resource = $adapter->readStream('path.txt');
        $metadata = stream_get_meta_data($resource);
        fclose($resource);

        $this->assertEquals($seekable, $metadata['seekable']);
    }

    public function casesWhereHttpStreamingInfluencesSeekability(): Generator
    {
        yield "not streaming reads have seekable stream" => [false, true];
        yield "streaming reads have non-seekable stream" => [true, false];
    }

    /**
     * @test
     * @dataProvider casesWhereHttpStreamingInfluencesSeekability
     */
    public function configuring_http_streaming_via_options(bool $streaming): void
    {
        $adapter = $this->useAdapter($this->createFilesystemAdapter($streaming, ['@http' => ['stream' => false]]));
        $this->givenWeHaveAnExistingFile('path.txt');

        $resource = $adapter->readStream('path.txt');
        $metadata = stream_get_meta_data($resource);
        fclose($resource);

        $this->assertTrue($metadata['seekable']);
    }

    /**
     * @test
     */
    public function moving_with_updated_metadata(): void
    {
        $adapter = $this->adapter();
        $adapter->write('source.txt', 'contents to be moved', new Config(['ContentType' => 'text/plain']));
        $mimeTypeSource = $adapter->mimeType('source.txt')->mimeType();
        $this->assertSame('text/plain', $mimeTypeSource);

        $adapter->move('source.txt', 'destination.txt', new Config(
            ['ContentType' => 'text/plain+special', 'MetadataDirective' => 'REPLACE']
        ));
        $mimeTypeDestination = $adapter->mimeType('destination.txt')->mimeType();
        $this->assertSame('text/plain+special', $mimeTypeDestination);
    }

    /**
     * @test
     */
    public function setting_acl_via_options(): void
    {
        $adapter = $this->adapter();
        $prefixer = new PathPrefixer(static::$adapterPrefix);
        $prefixedPath = $prefixer->prefixPath('path.txt');

        $adapter->write('path.txt', 'contents', new Config(['ACL' => 'bucket-owner-full-control']));
        $arguments = ['Bucket' => getenv('FLYSYSTEM_AWS_S3_BUCKET'), 'Key' => $prefixedPath];
        $command = static::$s3Client->getCommand('GetObjectAcl', $arguments);
        $response = static::$s3Client->execute($command)->toArray();
        $permission = $response['Grants'][0]['Permission'];

        self::assertEquals('FULL_CONTROL', $permission);
    }

    protected static function createFilesystemAdapter(bool $streaming = true, array $options = []): FilesystemAdapter
    {
        static::$stubS3Client = new S3ClientStub(static::s3Client());
        /** @var string $bucket */
        $bucket = getenv('FLYSYSTEM_AWS_S3_BUCKET');
        $prefix = static::$adapterPrefix;

        return new AwsS3V3Adapter(static::$stubS3Client, $bucket, $prefix, null, null, $options, $streaming);
    }
}
