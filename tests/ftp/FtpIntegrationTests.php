<?php

use League\Flysystem\Adapter\Ftp;
use League\Flysystem\AdapterInterface;

include_once __DIR__ . '/FtpIntegrationTestCase.php';

/**
 * @group integration
 */
class FtpIntegrationTests extends FtpIntegrationTestCase
{
    /**
     * @return AdapterInterface
     */
    protected static function setup_adapter()
    {
        return new Ftp([
            'host' => 'localhost',
            'username' => 'foo',
            'password' => 'pass',
            'port' => 2122,
            'recurseManually' => false,
        ]);
    }
}
