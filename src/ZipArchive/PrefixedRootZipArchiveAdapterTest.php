<?php

declare(strict_types=1);

namespace League\Flysystem\ZipArchive;

/**
 * @group zip
 */
final class PrefixedRootZipArchiveAdapterTest extends ZipArchiveAdapterTestCase
{
    protected static function getRoot(): string
    {
        return '/prefixed-path';
    }
}
