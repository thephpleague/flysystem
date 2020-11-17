<?php

declare(strict_types=1);

namespace League\Flysystem\ZipArchive;

use ZipArchive;

interface ZipArchiveProvider
{
    public function createZipArchive(): ZipArchive;
}
