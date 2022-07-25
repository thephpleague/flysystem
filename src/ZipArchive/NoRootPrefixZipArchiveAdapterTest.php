<?php

declare(strict_types=1);

namespace League\Flysystem\ZipArchive;

final class NoRootPrefixZipArchiveAdapterTest extends ZipArchiveAdapterTest
{
    protected static function getRoot(): string
    {
        return '/';
    }
}
