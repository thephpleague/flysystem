<?php

declare(strict_types=1);

namespace League\Flysystem\GoogleCloudStorage;

use Google\Cloud\Storage\StorageClient;
use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\FilesystemAdapter;

class GoogleCloudStorageAdapterTest extends FilesystemAdapterTestCase
{
    /**
     * @var string
     */
    private static $adapterPrefix = 'ci';

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
        $storageClient = new StorageClient($clientOptions);
        $bucket = $storageClient->bucket('flysystem');

        return new GoogleCloudStorageAdapter($bucket, static::$adapterPrefix);
    }
}
