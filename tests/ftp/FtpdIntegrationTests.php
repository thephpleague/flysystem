<?php

use League\Flysystem\Adapter\Ftpd;
use League\Flysystem\AdapterInterface;

include_once __DIR__ . '/FtpIntegrationTestCase.php';

/**
 * @group integration
 */
class FtpdIntegrationTests extends FtpIntegrationTestCase
{
    /**
     * @return AdapterInterface
     */
    protected function setup_adapter()
    {
        return new Ftpd([
            'host' => 'localhost',
            'username' => 'bob',
            'password' => 'test',
            'recurseManually' => false,
        ]);
    }
}
