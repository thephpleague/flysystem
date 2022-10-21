<?php

declare(strict_types=1);

namespace League\Flysystem\AzureBlobStorage;

use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase as TestCase;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\Visibility;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Internal\StorageServiceSettings;
use function getenv;

/**
 * @group azure
 */
class AzureBlobStorageAdapterTest extends TestCase
{
    const CONTAINER_NAME = 'flysystem';

    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        $dsn = getenv('FLYSYSTEM_AZURE_DSN');

        if (empty($dsn)) {
            self::markTestSkipped('FLYSYSTEM_AZURE_DSN is not provided.');
        }

        $client = BlobRestProxy::createBlobService($dsn);
        $serviceSettings = StorageServiceSettings::createFromConnectionString($dsn);

        return new AzureBlobStorageAdapter(
            $client,
            self::CONTAINER_NAME,
            'ci',
            serviceSettings: $serviceSettings,
        );
    }

    /**
     * @test
     */
    public function overwriting_a_file(): void
    {
        $this->runScenario(
            function () {
                $this->givenWeHaveAnExistingFile('path.txt', 'contents');
                $adapter = $this->adapter();

                $adapter->write('path.txt', 'new contents', new Config());

                $contents = $adapter->read('path.txt');
                $this->assertEquals('new contents', $contents);
            }
        );
    }

    /**
     * @test
     */
    public function setting_visibility(): void
    {
        self::markTestSkipped('Azure does not support visibility');
    }

    /**
     * @test
     */
    public function failing_to_set_visibility(): void
    {
        self::markTestSkipped('Azure does not support visibility');
    }

    /**
     * @test
     */
    public function failing_to_check_visibility(): void
    {
        self::markTestSkipped('Azure does not support visibility');
    }

    public function fetching_unknown_mime_type_of_a_file(): void
    {
        $this->markTestSkipped('This adapter always returns a mime-type');
    }

    public function listing_contents_recursive(): void
    {
        $this->markTestSkipped('This adapter does not support creating directories');
    }

    /**
     * @test
     */
    public function copying_a_file(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write(
                'source.txt',
                'contents to be copied',
                new Config([Config::OPTION_VISIBILITY => Visibility::PUBLIC])
            );

            $adapter->copy('source.txt', 'destination.txt', new Config());

            $this->assertTrue($adapter->fileExists('source.txt'));
            $this->assertTrue($adapter->fileExists('destination.txt'));
            $this->assertEquals('contents to be copied', $adapter->read('destination.txt'));
        });
    }

    /**
     * @test
     */
    public function moving_a_file(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write(
                'source.txt',
                'contents to be copied',
                new Config([Config::OPTION_VISIBILITY => Visibility::PUBLIC])
            );
            $adapter->move('source.txt', 'destination.txt', new Config());
            $this->assertFalse(
                $adapter->fileExists('source.txt'),
                'After moving a file should no longer exist in the original location.'
            );
            $this->assertTrue(
                $adapter->fileExists('destination.txt'),
                'After moving, a file should be present at the new location.'
            );
            $this->assertEquals('contents to be copied', $adapter->read('destination.txt'));
        });
    }

    /**
     * @test
     */
    public function copying_a_file_again(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write(
                'source.txt',
                'contents to be copied',
                new Config()
            );

            $adapter->copy('source.txt', 'destination.txt', new Config());

            $this->assertTrue($adapter->fileExists('source.txt'));
            $this->assertTrue($adapter->fileExists('destination.txt'));
            $this->assertEquals('contents to be copied', $adapter->read('destination.txt'));
        });
    }

    /**
     * @test
     */
    public function setting_visibility_can_be_ignored_not_supported(): void
    {
        $this->givenWeHaveAnExistingFile('some-file.md');
        $this->expectNotToPerformAssertions();

        $client = BlobRestProxy::createBlobService(getenv('FLYSYSTEM_AZURE_DSN'));
        $adapter = new AzureBlobStorageAdapter($client, self::CONTAINER_NAME, 'ci', null, 50000, AzureBlobStorageAdapter::ON_VISIBILITY_IGNORE);

        $adapter->setVisibility('some-file.md', 'public');
    }

    /**
     * @test
     */
    public function setting_visibility_causes_errors(): void
    {
        $this->givenWeHaveAnExistingFile('some-file.md');
        $adapter = $this->adapter();

        $this->expectException(UnableToSetVisibility::class);

        $adapter->setVisibility('some-file.md', 'public');
    }

    /**
     * @test
     */
    public function checking_if_a_directory_exists_after_creating_it(): void
    {
        $this->markTestSkipped('This adapter does not support creating directories');
    }

    /**
     * @test
     */
    public function setting_visibility_on_a_file_that_does_not_exist(): void
    {
        $this->markTestSkipped('This adapter does not support visibility');
    }

    /**
     * @test
     */
    public function creating_a_directory(): void
    {
        $this->markTestSkipped('This adapter does not support creating directories');
    }
}
