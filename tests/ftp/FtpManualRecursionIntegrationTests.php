<?php

use League\Flysystem\Adapter\Ftp;
use League\Flysystem\AdapterInterface;

include_once __DIR__ . '/FtpIntegrationTestCase.php';

/**
 * @group integration
 */
class FtpManualRecursionIntegrationTests extends FtpIntegrationTestCase
{
    /**
     * @return AdapterInterface
     */
    protected static function setup_adapter()
    {
        return new Ftp([
            'host' => 'localhost',
            'port' => 2122,
            'username' => 'foo',
            'password' => 'pass',
            'recurseManually' => true,
        ]);
    }
}
