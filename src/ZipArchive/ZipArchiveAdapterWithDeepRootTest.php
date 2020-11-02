<?php

declare(strict_types=1);

namespace League\Flysystem\ZipArchive;

use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\FilesystemAdapter;

final class ZipArchiveAdapterWithDeepRootTest extends FilesystemAdapterTestCase
{
    private const ARCHIVE = __DIR__ . '/test-with-deep-root.zip';

    protected function setUp(): void
    {
        if ( ! file_exists(self::ARCHIVE)) {
            return;
        }

        unlink(self::ARCHIVE);
    }

    protected function tearDown(): void
    {
        if ( ! file_exists(self::ARCHIVE)) {
            return;
        }

        unlink(self::ARCHIVE);
    }

    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        return new ZipArchiveAdapter(self::ARCHIVE, '/root/');
    }
}
