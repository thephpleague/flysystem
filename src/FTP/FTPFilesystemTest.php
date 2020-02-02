<?php

declare(strict_types=1);

namespace League\Flysystem\FTP;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemAdapterTestCase;

class FTPFilesystemTest extends FilesystemAdapterTestCase
{
    protected function createFilesystemAdapter(): FilesystemAdapter
    {
        $options = FTPConnectionOptions::fromArray([
            'host' => 'localhost',
            'port' => 2121,
            'recurseManually' => true,
            'root' => '/home/foo/upload/',
            'username' => 'foo',
            'password' => 'pass',
        ]);

        return new FTPFilesystem($options);
    }
}
