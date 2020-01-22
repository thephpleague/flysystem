<?php

declare(strict_types=1);

namespace League\Flysystem\PHPSecLibV2;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemAdapterTestCase;

class SftpFilesystemTest extends FilesystemAdapterTestCase
{
    /**
     * @var SftpConnectionProvider
     */
    private $connectionProvider;

    protected function createFilesystemAdapter(): FilesystemAdapter
    {
        return new SftpFilesystem(
            $this->connectionProvider(),
            '/upload'
        );
    }

    private function connectionProvider(): ConnectionProvider
    {
        if ( ! $this->connectionProvider instanceof SftpConnectionProvider) {
        }
        $this->connectionProvider = new SftpConnectionProvider('localhost', 'foo', 'pass', 2222);

        return $this->connectionProvider;
    }
}
