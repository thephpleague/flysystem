<?php

declare(strict_types=1);

namespace League\Flysystem\ZipArchive;

/**
 * @group zip
 */
final class NoRootPrefixZipArchiveAdapterTest extends ZipArchiveAdapterTestCase
{
    protected static function getRoot(): string
    {
        return '/';
    }
}
