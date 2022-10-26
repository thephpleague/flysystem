<?php

declare(strict_types=1);

namespace League\Flysystem\ZipArchive;

use ZipArchive;

class FilesystemZipArchiveProvider implements ZipArchiveProvider
{
    /**
     * @var bool
     */
    private $parentDirectoryCreated = false;

    public function __construct(private string $filename, private int $localDirectoryPermissions = 0700)
    {
    }

    public function createZipArchive(): ZipArchive
    {
        if ($this->parentDirectoryCreated !== true) {
            $this->parentDirectoryCreated = true;
            $this->createParentDirectoryForZipArchive($this->filename);
        }

        return $this->openZipArchive();
    }

    private function createParentDirectoryForZipArchive(string $fullPath): void
    {
        $dirname = dirname($fullPath);

        if (is_dir($dirname) || @mkdir($dirname, $this->localDirectoryPermissions, true)) {
            return;
        }

        if ( ! is_dir($dirname)) {
            throw UnableToCreateParentDirectory::atLocation($fullPath, error_get_last()['message'] ?? '');
        }
    }

    private function openZipArchive(): ZipArchive
    {
        $archive = new ZipArchive();
        $success = $archive->open($this->filename, ZipArchive::CREATE);

        if ($success !== true) {
            throw UnableToOpenZipArchive::atLocation($this->filename, $archive->getStatusString() ?: '');
        }

        return $archive;
    }
}
