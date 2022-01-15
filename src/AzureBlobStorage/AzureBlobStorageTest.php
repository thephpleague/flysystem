<?php

declare(strict_types=1);

namespace League\Flysystem\AzureBlobStorage;

use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase as TestCase;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use function getenv;

class AzureBlobStorageTest extends TestCase
{
    const CONTAINER_NAME = 'flysystem';

    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        $client = BlobRestProxy::createBlobService(getenv('FLYSYSTEM_AZURE_DSN'));

        return new AzureBlobStorageAdapter($client, new StaticContainerPathResolver(self::CONTAINER_NAME));
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
}
