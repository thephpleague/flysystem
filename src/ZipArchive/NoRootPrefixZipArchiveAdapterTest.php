<?php

declare(strict_types=1);

namespace League\Flysystem\ZipArchive;

/**
 * @group zip
 */
final class NoRootPrefixZipArchiveAdapterTest extends ZipArchiveAdapterTest
{
    protected static function getRoot(): string
    {
        return '/';
    }
}
