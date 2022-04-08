<?php

declare(strict_types=1);

namespace League\Flysystem\GoogleCloudStorage;

use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
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

    /**
     * @var StubBucket
     */
    private static $bucket;

    public static function setUpBeforeClass(): void
    {
        static::$adapterPrefix = 'ci/' . bin2hex(random_bytes(10));
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
        $connection = $storageClient->connection();
        $projectId = $storageClient->projectId();

        static::$bucket = $bucket = new StubBucket($connection, 'flysystem', [
            'requesterProjectId' => $projectId,
        ]);

        return new GoogleCloudStorageAdapter($bucket, static::$adapterPrefix);
    }

    /**
     * @test
     */
    public function writing_with_specific_metadata(): void
    {
        $adapter = $this->adapter();
        $adapter->write('some/path.txt', 'contents', new Config(['metadata' => ['contentType' => 'text/plain+special']]));
        $mimeType = $adapter->mimeType('some/path.txt')->mimeType();
        $this->assertEquals('text/plain+special', $mimeType);
    }

    /**
     * @test
     */
    public function guessing_the_mime_type_when_writing(): void
    {
        $adapter = $this->adapter();
        $adapter->write('some/config.txt', '<?xml version="1.0" encoding="UTF-8"?><test/>', new Config());
        $mimeType = $adapter->mimeType('some/config.txt')->mimeType();
        $this->assertEquals('text/xml', $mimeType);
    }

    /**
     * @test
     */
    public function fetching_visibility_of_non_existing_file(): void
    {
        $this->markTestSkipped("
            Not relevant for this adapter since it's a missing ACL,
            which turns into a 404 which is the expected outcome
            of a private visibility. 🤷‍♂️
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
        static::$bucket->failOnUpload();

        $this->expectException(UnableToWriteFile::class);

        $adapter->write('something.txt', 'contents', new Config());
    }

    /**
     * @test
     */
    public function failing_to_delete_a_file(): void
    {
        $adapter = $this->adapter();
        static::$bucket->withObject(static::$adapterPrefix . '/filename.txt')->failWhenDeleting();

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
        static::$bucket->withObject(static::$adapterPrefix . '/dir/filename.txt')->failWhenDeleting();

        $this->expectException(UnableToDeleteDirectory::class);

        $adapter->deleteDirectory('dir');
    }

    /**
     * @test
     */
    public function failing_to_retrieve_visibility(): void
    {
        $adapter = $this->adapter();
        static::$bucket->withObject(static::$adapterPrefix . '/filename.txt')->failWhenAccessingAcl();

        $this->expectException(UnableToRetrieveMetadata::class);

        $adapter->visibility('filename.txt');
    }
}
