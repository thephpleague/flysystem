<?php

declare(strict_types=1);

namespace League\Flysystem\GoogleCloudStorage;

use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;

/**
 * @group gcs
 */
class GoogleCloudStorageAdapterTest extends FilesystemAdapterTestCase
{
    /**
     * @var string
     */
    private static $adapterPrefix = 'ci';
    private static StubRiggedBucket $bucket;
    private static PathPrefixer $prefixer;

    public static function setUpBeforeClass(): void
    {
        static::$adapterPrefix = 'frank-ci'; // . bin2hex(random_bytes(10));
        static::$prefixer = new PathPrefixer(static::$adapterPrefix);
    }

    public function prefixPath(string $path): string
    {
        return static::$prefixer->prefixPath($path);
    }

    public function prefixDirectoryPath(string $path): string
    {
        return static::$prefixer->prefixDirectoryPath($path);
    }

    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        if ( ! file_exists(__DIR__ . '/../../google-cloud-service-account.json')) {
            self::markTestSkipped("No google service account found in project root.");
        }

        $clientOptions = [
            'projectId' => 'flysystem-testing',
            'keyFilePath' => __DIR__ . '/../../google-cloud-service-account.json',
        ];
        $storageClient = new StubStorageClient($clientOptions);
        static::$bucket = $bucket = $storageClient->bucket('flysystem');

        return new GoogleCloudStorageAdapter($bucket, static::$adapterPrefix);
    }

    /**
     * @test
     */
    public function fetching_visibility_of_non_existing_file(): void
    {
        $this->markTestSkipped("
            Not relevant for this adapter since it's a missing ACL,
            which turns into a 404 which is the expected outcome
            of a private visibility. ¯\_(ツ)_/¯
        ");
    }

    /**
     * @test
     */
    public function fetching_unknown_mime_type_of_a_file(): void
    {
        $this->markTestSkipped("This adapter always returns a mime-type.");
    }

    /**
     * @test
     */
    public function listing_a_toplevel_directory(): void
    {
        $this->clearStorage();
        parent::listing_a_toplevel_directory();
    }

    /**
     * @test
     */
    public function failing_to_write_a_file(): void
    {
        $adapter = $this->adapter();
        static::$bucket->failForUpload($this->prefixPath('something.txt'));

        $this->expectException(UnableToWriteFile::class);

        $adapter->write('something.txt', 'contents', new Config());
    }

    /**
     * @test
     */
    public function failing_to_delete_a_file(): void
    {
        $adapter = $this->adapter();
        static::$bucket->failForObject($this->prefixPath('filename.txt'));

        $this->expectException(UnableToDeleteFile::class);

        $adapter->delete('filename.txt');
    }

    /**
     * @test
     */
    public function failing_to_delete_a_directory(): void
    {
        $adapter = $this->adapter();
        $this->givenWeHaveAnExistingFile('dir/filename.txt');

        static::$bucket->failForObject($this->prefixPath('dir/filename.txt'));

        $this->expectException(UnableToDeleteDirectory::class);

        $adapter->deleteDirectory('dir');
    }

    /**
     * @test
     */
    public function failing_to_retrieve_visibility(): void
    {
        $adapter = $this->adapter();
        static::$bucket->failForObject($this->prefixPath('filename.txt'));

        $this->expectException(UnableToRetrieveMetadata::class);

        $adapter->visibility('filename.txt');
    }
}
