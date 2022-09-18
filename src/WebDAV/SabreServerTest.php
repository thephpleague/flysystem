<?php

declare(strict_types=1);

namespace League\Flysystem\WebDAV;

use League\Flysystem\FilesystemAdapter;
use Sabre\DAV\Client;

class SabreServerTest extends WebDAVAdapterTestCase
{
    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        $client = new Client(['baseUri' => 'http://localhost:4040/']);

        return new WebDAVAdapter($client, 'directory/prefix');
    }
}
