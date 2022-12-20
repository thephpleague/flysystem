<?php

declare(strict_types=1);

namespace League\Flysystem\WebDAV;

use League\Flysystem\FilesystemAdapter;
use Sabre\DAV\Client;

class ByteMarkWebDAVServerTest extends WebDAVAdapterTestCase
{
    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        $client = new UrlPrefixingClientStub(['baseUri' => 'http://localhost:4080/', 'userName' => 'alice', 'password' => 'secret1234']);

        return new WebDAVAdapter($client, manualCopy: true, manualMove: true);
    }
}
