<?php

declare(strict_types=1);

namespace League\Flysystem\AwsS3V3;

use Aws\Command;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\Visibility;
use PHPUnit\Framework\TestCase;

class AwsS3V3FilesystemTest extends TestCase
{
    private $shouldCleanUp = false;

    private static $adapterPrefix = 'test-prefix';

    /**
     * @var S3ClientInterface
     */
    private $s3Client;

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

    private function adapter(S3ClientInterface $client = null): AwsS3V3Filesystem
    {
        $client = $client ?: $this->s3Client();
        $bucket = getenv('FLYSYSTEM_AWS_S3_BUCKET');
        $prefix = getenv('FLYSYSTEM_AWS_S3_PREFIX') ?: static::$adapterPrefix;

        return new AwsS3V3Filesystem($client, $bucket, $prefix);
    }

    private function stubS3Client(): S3ClientStub
    {
        return new S3ClientStub($this->s3Client());
    }

    /**
     * @test
     */
    public function writing_and_reading()
    {
        $adapter = $this->adapter();
        $adapter->write('some/path.txt', 'contents', new Config());

        $contents = $adapter->read('some/path.txt');

        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function updating_and_reading()
    {
        $adapter = $this->adapter();

        $adapter->update('some/path.txt', 'contents', new Config());

        $contents = $adapter->read('some/path.txt');
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function writing_and_reading_with_streams()
    {
        $writeStream = stream_with_contents('contents');
        $adapter = $this->adapter();

        $adapter->writeStream('path.txt', $writeStream, new Config());
        fclose($writeStream);
        $readStream = $adapter->readStream('path.txt');

        $this->assertIsResource($readStream);
        $contents = stream_get_contents($readStream);
        fclose($readStream);
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function updating_and_reading_with_streams()
    {
        $writeStream = stream_with_contents('contents');
        $adapter = $this->adapter();

        $adapter->updateStream('path.txt', $writeStream, new Config());
        fclose($writeStream);
        $readStream = $adapter->readStream('path.txt');

        $this->assertIsResource($readStream);
        $contents = stream_get_contents($readStream);
        fclose($readStream);
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function reading_a_file_that_does_not_exist()
    {
        $this->expectException(UnableToReadFile::class);
        $this->adapter()->read('path.txt');
    }

    /**
     * @test
     */
    public function setting_visibility()
    {
        $adapter = $this->adapter();
        $adapter->write('some/path.txt', 'contents', new Config(['visibility' => Visibility::PUBLIC]));
        $this->assertEquals(Visibility::PUBLIC, $adapter->visibility('some/path.txt')->visibility());
        $adapter->setVisibility('some/path.txt', Visibility::PRIVATE);
        $this->assertEquals(Visibility::PRIVATE, $adapter->visibility('some/path.txt')->visibility());
        $adapter->setVisibility('some/path.txt', Visibility::PUBLIC);
        $this->assertEquals(Visibility::PUBLIC, $adapter->visibility('some/path.txt')->visibility());
    }

    /**
     * @test
     */
    public function setting_visibility_on_a_file_that_does_not_exist()
    {
        $this->expectException(UnableToSetVisibility::class);
        $this->adapter()->setVisibility('path.txt', Visibility::PRIVATE);
    }

    /**
     * @test
     */
    public function writing_with_a_specific_mime_type()
    {
        $adapter = $this->adapter();
        $adapter->write('some/path.txt', 'contents', new Config(['ContentType' => 'text/plain+special']));
        $mimeType = $adapter->mimeType('some/path.txt')->mimeType();
        $this->assertEquals('text/plain+special', $mimeType);
    }

    /**
     * @test
     */
    public function checking_if_files_exist()
    {
        $adapter = $this->adapter();

        $this->assertFalse($adapter->fileExists('some/path.txt'));

        $adapter->write('some/path.txt', 'contents', new Config());

        $this->assertTrue($adapter->fileExists('some/path.txt'));
    }

    /**
     * @test
     */
    public function fetching_last_modified()
    {
        $adapter = $this->adapter();
        $adapter->write('path.txt', 'contents', new Config());

        $attributes = $adapter->lastModified('path.txt');

        $this->assertInstanceOf(FileAttributes::class, $attributes);
        $this->assertIsInt($attributes->lastModified());
        $this->assertTrue($attributes->lastModified() > time() - 30);
        $this->assertTrue($attributes->lastModified() < time() + 30);
    }

    /**
     * @test
     */
    public function fetching_file_size()
    {
        $adapter = $this->adapter();
        $adapter->write('path.txt', 'contents', new Config());

        $attributes = $adapter->fileSize('path.txt');

        $this->assertInstanceOf(FileAttributes::class, $attributes);
        $this->assertEquals(8, $attributes->fileSize());
    }

    /**
     * @test
     */
    public function fetching_file_size_of_a_directory()
    {
        $this->expectException(UnableToRetrieveMetadata::class);

        $adapter = $this->adapter();
        $adapter->createDirectory('path', new Config());

        $adapter->fileSize('path/');
    }

    /**
     * @test
     */
    public function failing_to_fetch_last_modified()
    {
        $this->expectException(UnableToRetrieveMetadata::class);

        $client = $this->stubS3Client();
        $client->throwExceptionWhenExecutingCommand('HeadObject');
        $adapter = $this->adapter($client);
        $adapter->createDirectory('path', new Config());

        $adapter->lastModified('path');
    }

    /**
     * @test
     */
    public function listing_contents_shallow()
    {
        $adapter = $this->adapter();
        $adapter->write('0_something/here.txt', 'contents', new Config());
        $adapter->write('1_here.txt', 'contents', new Config());

        $contents = iterator_to_array($adapter->listContents('', false));

        $this->assertCount(2, $contents);
        $this->assertContainsOnlyInstancesOf(StorageAttributes::class, $contents);
        /** @var DirectoryAttributes $directory */
        $directory = $contents[0];
        $this->assertInstanceOf(DirectoryAttributes::class, $directory);
        $this->assertEquals('0_something/', $directory->path());
        /** @var FileAttributes $directory */
        $file = $contents[1];
        $this->assertInstanceOf(FileAttributes::class, $file);
        $this->assertEquals('1_here.txt', $file->path());
    }

    /**
     * @test
     */
    public function listing_contents_recursive()
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
    public function copying_a_file()
    {
        $adapter = $this->adapter();
        $adapter->write('source.txt', 'contents to be copied', new Config(['visibility' => Visibility::PUBLIC]));

        $adapter->copy('source.txt', 'destination.txt', new Config());

        $this->assertTrue($adapter->fileExists('source.txt'));
        $this->assertTrue($adapter->fileExists('destination.txt'));
        $this->assertEquals(Visibility::PUBLIC, $adapter->visibility('destination.txt')->visibility());
        $this->assertEquals('contents to be copied', $adapter->read('destination.txt'));
    }

    /**
     * @test
     */
    public function moving_a_file()
    {
        $adapter = $this->adapter();
        $adapter->write('source.txt', 'contents to be copied', new Config(['visibility' => Visibility::PUBLIC]));
        $adapter->move('source.txt', 'destination.txt', new Config());
        $this->assertFalse($adapter->fileExists('source.txt'));
        $this->assertTrue($adapter->fileExists('destination.txt'));
        $this->assertEquals(Visibility::PUBLIC, $adapter->visibility('destination.txt')->visibility());
        $this->assertEquals('contents to be copied', $adapter->read('destination.txt'));
    }

    /**
     * @test
     */
    public function moving_a_file_that_does_not_exist()
    {
        $this->expectException(UnableToMoveFile::class);
        $adapter = $this->adapter();
        $adapter->move('source.txt', 'destination.txt', new Config());
    }

    /**
     * @test
     */
    public function failing_to_delete_while_moving()
    {
        $this->expectException(UnableToMoveFile::class);

        $client = $this->stubS3Client();
        $adapter = $this->adapter($client);
        $adapter->write('source.txt', 'contents to be copied', new Config());
        $client->throwExceptionWhenExecutingCommand('CopyObject');

        $adapter->move('source.txt', 'destination.txt', new Config());
    }

    /**
     * @test
     */
    public function deleting_a_file()
    {
        $adapter = $this->adapter();
        $adapter->write('path.txt', 'contents', new Config());

        $adapter->delete('path.txt');

        $this->assertFalse($adapter->fileExists('path.txt'));
    }

    /**
     * @test
     */
    public function trying_to_delete_a_non_existing_file()
    {
        $adapter = $this->adapter();

        $adapter->delete('path.txt');

        $this->assertFalse($adapter->fileExists('path.txt'));
    }

    /**
     * @test
     */
    public function failing_to_delete_a_file()
    {
        $this->expectException(UnableToDeleteFile::class);

        $client = $this->stubS3Client();
        $client->throwExceptionWhenExecutingCommand('DeleteObject');
        $adapter = $this->adapter($client);

        $adapter->delete('path.txt');
    }

    /**
     * @test
     */
    public function creating_a_directory()
    {
        $adapter = $this->adapter();

        $adapter->createDirectory('path', new Config());

        $contents = iterator_to_array($adapter->listContents('', false));
        $this->assertCount(1, $contents);
        /** @var DirectoryAttributes $directory */
        $directory = $contents[0];
        $this->assertInstanceOf(DirectoryAttributes::class, $directory);
        $this->assertEquals('path/', $directory->path());
    }
}
