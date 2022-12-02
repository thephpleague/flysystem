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
use function getenv;

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

    protected static function bucketName(): string|array|false
    {
        return 'flysystem';
    }

    protected static function visibilityHandler(): VisibilityHandler
    {
        return new PortableVisibilityHandler();
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
            'projectId' => getenv('GOOGLE_CLOUD_PROJECT'),
            'keyFilePath' => __DIR__ . '/../../google-cloud-service-account.json',
        ];
        $storageClient = new StubStorageClient($clientOptions);
        /** @var StubRiggedBucket $bucket */
        $bucket = $storageClient->bucket(self::bucketName());
        static::$bucket = $bucket;

        return new GoogleCloudStorageAdapter(
            $bucket,
            static::$adapterPrefix,
            visibilityHandler: self::visibilityHandler(),
        );
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
