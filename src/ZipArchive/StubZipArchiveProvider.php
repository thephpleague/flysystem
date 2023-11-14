<?php

declare(strict_types=1);

namespace League\Flysystem\ZipArchive;

use ZipArchive;

class StubZipArchiveProvider implements ZipArchiveProvider
{
    private FilesystemZipArchiveProvider $provider;

    /**
     * @var StubZipArchive
     */
    private $archive;

    public function __construct(private string $filename, int $localDirectoryPermissions = 0700)
    {
        $this->provider = new FilesystemZipArchiveProvider($filename, $localDirectoryPermissions);
    }

    public function createZipArchive(): ZipArchive
    {
        if ( ! $this->archive instanceof StubZipArchive) {
            $zipArchive = $this->provider->createZipArchive();
            $zipArchive->close();
            unset($zipArchive);
            $this->archive = new StubZipArchive();
        }

        $this->archive->open($this->filename, ZipArchive::CREATE);

        return $this->archive;
    }

    public function stubbedZipArchive(): StubZipArchive
    {
        $this->createZipArchive();

        return $this->archive;
    }
}
