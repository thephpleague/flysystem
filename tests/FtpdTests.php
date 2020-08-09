<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Config;
use PHPUnit\Framework\TestCase;

class FtpdTests extends TestCase
{

    protected $options = [
        'host' => 'example.org',
        'port' => 40,
        'ssl' => true,
        'timeout' => 35,
        'root' => '/somewhere',
        'permPublic' => 0777,
        'permPrivate' => 0000,
        'passive' => false,
        'username' => 'user',
        'password' => 'password',
    ];

    public function testInstantiable()
    {
        if ( ! defined('FTP_BINARY')) {
            $this->markTestSkipped('The FTP_BINARY constant is not defined');
        }

        $adapter = new Ftpd($this->options);
        $listing = $adapter->listContents('', true);
        $this->assertIsArray($listing);
        $this->assertFalse($adapter->has('syno.not.found'));
        $result = $adapter->getMimetype('something.txt');
        $this->assertEquals('text/plain', $result['mimetype']);
        $this->assertIsArray($adapter->write('syno.unknowndir/file.txt', 'contents', new Config(['visibility' => 'public'])));
        $this->assertIsArray($adapter->getTimestamp('some/file.ext'));
    }

    /**
     * @depends testInstantiable
     */
    public function testGetExistingDirMetadata()
    {
        $adapter = new Ftpd($this->options);
        $dirMetadata = $adapter->getMetadata('spaced.files');
        $this->assertSame(['type' => 'dir', 'path' => 'spaced.files'], $dirMetadata);
    }

    /**
     * @depends testInstantiable
     */
    public function testGetMissingDirMetadata()
    {
        $adapter = new Ftpd($this->options);
        $dirMetadata = $adapter->getMetadata('syno.not.found');
        $this->assertFalse($dirMetadata);
    }

    /**
     * @depends testInstantiable
     */
    public function testRawlistFail()
    {
        $adapter = new Ftpd($this->options);
        $result = $adapter->listContents('fail.rawlist');
        $this->assertEquals([], $result);
    }

    /**
     * @depends testInstantiable
     */
    public function testGetMetadata()
    {
        $adapter = new Ftpd($this->options);
        $result = $adapter->getMetadata('something.txt');
        $this->assertNotEmpty($result);
    }

    /**
     * @depends testInstantiable
     */
    public function testGetMetadataOnRoot()
    {
        $adapter = new Ftpd($this->options);
        $result = $adapter->getMetadata('');
        $this->assertNotEmpty($result);
    }

    /**
     * @depends testInstantiable
     */
    public function testSynologyFtpLegacyClassName()
    {
        $adapter = new SynologyFtp($this->options);
        $this->assertInstanceOf('League\Flysystem\Adapter\Ftpd', $adapter);
    }
}
