<?php

namespace League\Flysystem\Adapter;

use ErrorException;
use League\Flysystem\Config;

class FtpTests extends \PHPUnit_Framework_TestCase
{
    const RESOURCES_PATH = __DIR__ . '/../resources/';

    protected $adapter;

    protected function getResourceContent($path)
    {
        return file_get_contents($this->getResourceAbsolutePath($path));
    }

    protected function getResourceAbsolutePath($path)
    {
        return static::RESOURCES_PATH . $path;
    }

    protected function clearResources()
    {
        exec('rm -rf ' . escapeshellarg(static::RESOURCES_PATH) . '*');
        exec('rm -rf ' . escapeshellarg(static::RESOURCES_PATH) . '.* 2>/dev/null');
    }

    public function setUp()
    {
        $this->adapter = new Ftp([
            'host' => getenv('FTP_ADAPTER_HOST'),
            'port' => getenv('FTP_ADAPTER_PORT'),
            'username' => getenv('FTP_ADAPTER_USER'),
            'password' => getenv('FTP_ADAPTER_PASSWORD'),
            'ssl' => getenv('FTP_ADAPTER_SSL') == 'true',
            'timeout' => getenv('FTP_ADAPTER_TIMEOUT') ?: 35,
        ]);
    }

    public function tearDown()
    {
        unset($this->adapter);
        $this->clearResources();
    }

    /**
     * @dataProvider filenameProvider
     */
    public function testRead($filename)
    {
        $filedata = 'testdata';
        file_put_contents($this->getResourceAbsolutePath($filename), $filedata);

        $response = $this->adapter->read($filename);
        $this->assertEquals($filedata, $response['contents']);
    }

    /**
     * @dataProvider filenameProvider
     */
    public function testWrite($filename)
    {
        $filedata = 'testdata';

        $this->adapter->write($filename, $filedata, new Config);
        $this->assertEquals($filedata, $this->getResourceContent($filename));
    }

    /**
     * @dataProvider filenameProvider
     */
    public function testHas($filename)
    {
        $filedata = 'testdata';
        file_put_contents($this->getResourceAbsolutePath($filename), $filedata);

        $this->assertTrue((bool) $this->adapter->has($filename));
    }

    public function filenameProvider()
    {
        return [
            ['test.txt'],
            ['..test.txt'],
            ['test 1.txt'],
            ['тест.txt'],
        ];
    }
}
