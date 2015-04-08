<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Config;

class FtpdTests extends \PHPUnit_Framework_TestCase
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
        if (!defined('FTP_BINARY')) {
            $this->markTestSkipped('The FTP_BINARY constant is not defined');
        }

        $adapter = new Ftpd($this->options);
        $listing = $adapter->listContents('', true);
        $this->assertInternalType('array', $listing);
        $this->assertFalse($adapter->has('syno.not.found'));
        $result = $adapter->getMimetype('something.txt');
        $this->assertEquals('text/plain', $result['mimetype']);
        $this->assertInternalType('array', $adapter->write('syno.unknowndir/file.txt', 'contents', new Config(['visibility' => 'public'])));
        $this->assertInternalType('array', $adapter->getTimestamp('some/file.ext'));
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
    public function testSynologyFtpLegacyClassName()
    {
        $adapter = new SynologyFtp($this->options);
        $this->assertInstanceOf('League\Flysystem\Adapter\Ftpd', $adapter);
    }
}
