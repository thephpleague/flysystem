<?php

declare(strict_types=1);

namespace League\Flysystem\AsyncAwsS3;

use AsyncAws\Core\Exception\Http\ClientException;
use AsyncAws\Core\Exception\Http\NetworkException;
use AsyncAws\Core\Test\Http\SimpleMockedResponse;
use AsyncAws\Core\Test\ResultMockFactory;
use AsyncAws\S3\Result\HeadObjectOutput;
use AsyncAws\S3\Result\PutObjectOutput;
use AsyncAws\S3\S3Client;
use AsyncAws\SimpleS3\SimpleS3Client;
use Exception;
use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\ChecksumAlgoIsNotSupported;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\Visibility;
use function iterator_to_array;

/**
 * @group aws
 */
class AsyncAwsS3AdapterTest extends FilesystemAdapterTestCase
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
     * @var S3Client|null
     */
    private static $s3Client;

    /**
     * @var S3ClientStub
     */
    private static $stubS3Client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->retryOnException(NetworkException::class);
    }

    public static function setUpBeforeClass(): void
    {
        static::$adapterPrefix = 'ci/' . bin2hex(random_bytes(10));
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
    }

    private static function s3Client(): S3Client
    {
        if (static::$s3Client instanceof S3Client) {
            return static::$s3Client;
        }

        $key = getenv('FLYSYSTEM_AWS_S3_KEY');
        $secret = getenv('FLYSYSTEM_AWS_S3_SECRET');
        $bucket = getenv('FLYSYSTEM_AWS_S3_BUCKET');
        $region = getenv('FLYSYSTEM_AWS_S3_REGION') ?: 'eu-central-1';

        if ( ! $key || ! $secret || ! $bucket) {
            self::markTestSkipped('No AWS credentials present for testing.');
        }

        static::$s3Client = new SimpleS3Client([
            'accessKeyId' => $key,
            'accessKeySecret' => $secret,
            'region' => $region,
        ]);

        return static::$s3Client;
    }



    /**
     * @test
     */
    public function specifying_a_custom_checksum_algo_is_not_supported(): void
    {
        /** @var AwsS3V3Adapter $adapter */
        $adapter = $this->adapter();

        $this->expectException(ChecksumAlgoIsNotSupported::class);

        $adapter->checksum('something', new Config(['checksum_algo' => 'md5']));
    }

    /**
     * @test
     *
     * @see https://github.com/thephpleague/flysystem-aws-s3-v3/issues/287
     */
    public function issue_287(): void
    {
        $adapter = $this->adapter();
        $adapter->write('KmFVvKqo/QLMExy2U/620ff60c8a154.pdf', 'pdf content', new Config());

        self::assertTrue($adapter->directoryExists('KmFVvKqo'));
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
        static::$stubS3Client->throwExceptionWhenExecutingCommand('CopyObject');

        $this->expectException(UnableToMoveFile::class);

        $adapter->move('source.txt', 'destination.txt', new Config());
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
        $result = ResultMockFactory::create(HeadObjectOutput::class, []);
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
        $result = ResultMockFactory::create(HeadObjectOutput::class, []);
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
        $exception = new ClientException(new SimpleMockedResponse());
        static::$stubS3Client->throwExceptionWhenExecutingCommand('ObjectExists', $exception);

        $this->expectException(UnableToCheckFileExistence::class);

        $adapter->fileExists('something-that-does-exist.txt');
    }

    /**
     * @test
     */
    public function configuring_http_streaming_via_options(): void
    {
        $adapter = $this->useAdapter($this->createFilesystemAdapter());
        $this->givenWeHaveAnExistingFile('path.txt');

        $resource = $adapter->readStream('path.txt');
        $metadata = stream_get_meta_data($resource);
        fclose($resource);

        $this->assertTrue($metadata['seekable']);
    }

    /**
     * @test
     */
    public function write_with_s3_client(): void
    {
        $file = 'foo/bar.txt';
        $prefix = 'all-files';
        $bucket = 'foobar';
        $contents = 'contents';

        $s3Client = $this->getMockBuilder(S3Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['putObject'])
            ->getMock();
        $s3Client->expects(self::once())
            ->method('putObject')
            ->with(self::callback(function (array $input) use ($file, $prefix, $bucket, $contents) {
                if ($input['Key'] !== $prefix . '/' . $file) {
                    return false;
                }
                if ($contents !== $input['Body']) {
                    return false;
                }
                if ($input['Bucket'] !== $bucket) {
                    return false;
                }

                return true;
            }))->willReturn(ResultMockFactory::create(PutObjectOutput::class));

        $filesystem = new AsyncAwsS3Adapter($s3Client, $bucket, $prefix);
        $filesystem->write($file, $contents, new Config());
    }

    /**
     * @test
     */
    public function write_with_simple_s3_client(): void
    {
        $file = 'foo/bar.txt';
        $prefix = 'all-files';
        $bucket = 'foobar';
        $contents = 'contents';

        $s3Client = $this->getMockBuilder(SimpleS3Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['upload', 'putObject'])
            ->getMock();
        $s3Client->expects(self::never())->method('putObject');
        $s3Client->expects(self::once())
            ->method('upload')
            ->with($bucket, $prefix . '/' . $file, $contents);

        $filesystem = new AsyncAwsS3Adapter($s3Client, $bucket, $prefix);
        $filesystem->write($file, $contents, new Config());
    }

    /**
     * @test
     */
    public function moving_a_file_with_visibility(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write(
                'source.txt',
                'contents to be copied',
                new Config([Config::OPTION_VISIBILITY => Visibility::PUBLIC])
            );
            $adapter->move('source.txt', 'destination.txt', new Config([Config::OPTION_VISIBILITY => Visibility::PRIVATE]));
            $this->assertFalse(
                $adapter->fileExists('source.txt'),
                'After moving a file should no longer exist in the original location.'
            );
            $this->assertTrue(
                $adapter->fileExists('destination.txt'),
                'After moving, a file should be present at the new location.'
            );
            $this->assertEquals(Visibility::PRIVATE, $adapter->visibility('destination.txt')->visibility());
            $this->assertEquals('contents to be copied', $adapter->read('destination.txt'));
        });
    }

    /**
     * @test
     */
    public function copying_a_file_with_visibility(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write(
                'source.txt',
                'contents to be copied',
                new Config([Config::OPTION_VISIBILITY => Visibility::PUBLIC])
            );

            $adapter->copy('source.txt', 'destination.txt', new Config([Config::OPTION_VISIBILITY => Visibility::PRIVATE]));

            $this->assertTrue($adapter->fileExists('source.txt'));
            $this->assertTrue($adapter->fileExists('destination.txt'));
            $this->assertEquals(Visibility::PRIVATE, $adapter->visibility('destination.txt')->visibility());
            $this->assertEquals('contents to be copied', $adapter->read('destination.txt'));
        });
    }

    /**
     * @test
     */
    public function top_level_directory_excluded_from_listing(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write('directory/file.txt', '', new Config());
            $adapter->createDirectory('empty', new Config());
            $adapter->createDirectory('nested/nested', new Config());
            $listing1 = iterator_to_array($adapter->listContents('directory', true));
            $listing2 = iterator_to_array($adapter->listContents('empty', true));
            $listing3 = iterator_to_array($adapter->listContents('nested', true));

            self::assertCount(1, $listing1);
            self::assertCount(0, $listing2);
            self::assertCount(1, $listing3);
        });
    }

    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        static::$stubS3Client = new S3ClientStub(static::s3Client());
        /** @var string $bucket */
        $bucket = getenv('FLYSYSTEM_AWS_S3_BUCKET');
        $prefix = getenv('FLYSYSTEM_AWS_S3_PREFIX') ?: static::$adapterPrefix;

        return new AsyncAwsS3Adapter(static::$stubS3Client, $bucket, $prefix, null, null);
    }
}
