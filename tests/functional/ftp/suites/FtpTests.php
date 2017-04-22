<?php

namespace League\Flysystem\Adapter;

use ErrorException;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;

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
        return implode('/', array_filter([
            rtrim(static::RESOURCES_PATH, '/'),
            trim($this->root, '/'),
            ltrim($path, '/')
        ]));
    }

    protected function createResourceDir($path)
    {
        if (empty($path)) {
            return;
        }
        $absolutePath = $this->getResourceAbsolutePath($path);
        if (!is_dir($absolutePath)) {
            $umask = umask(0);
            mkdir($absolutePath, 0777, true);
            umask($umask);
        }
    }

    protected function createResourceFile($path, $filedata = '')
    {
        $this->createResourceDir(dirname($path));
        $absolutePath = $this->getResourceAbsolutePath($path);
        file_put_contents($absolutePath, $filedata);
    }

    protected function clearResources()
    {
        exec('rm -rf ' . escapeshellarg(static::RESOURCES_PATH) . '*');
        exec('rm -rf ' . escapeshellarg(static::RESOURCES_PATH) . '.* 2>/dev/null');
        clearstatcache();
    }

    public function setUp()
    {
        $this->root = '';
        $this->createResourceDir('/');

        $this->adapter = new Ftp([
            'host' => getenv('FTP_ADAPTER_HOST'),
            'port' => getenv('FTP_ADAPTER_PORT'),
            'username' => getenv('FTP_ADAPTER_USER'),
            'password' => getenv('FTP_ADAPTER_PASSWORD'),
            'ssl' => getenv('FTP_ADAPTER_SSL') == 'true',
            'timeout' => getenv('FTP_ADAPTER_TIMEOUT') ?: 35,
            'root' => $this->root,
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
        $this->createResourceFile($filename, $filedata);

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
        $this->createResourceFile($filename, $filedata);

        $this->assertTrue((bool) $this->adapter->has($filename));
    }

    /**
     * @dataProvider filepathProvider
     */
    public function testHasInSubFolder($filepath)
    {
        $filedata = 'testdata';
        $this->createResourceFile($filepath, $filedata);

        $this->assertTrue((bool) $this->adapter->has($filepath));
    }

    /**
     * @dataProvider filepathProvider
     */
    public function testListContents($filepath)
    {
        $filedata = 'testdata';
        $this->createResourceFile($filepath, $filedata);

        $this->assertEquals(1, count($this->adapter->listContents(dirname($filepath))));
    }

    public function filenameProvider()
    {
        return [
            ['test.txt'],
            ['..test.txt'],
            ['test 1.txt'],
            ['test  2.txt'],
            ['тест.txt'],
        ];
    }

    public function filepathProvider()
    {
        return [
            ['test/test.txt'],
            ['тёст/тёст.txt'],
            ['test 1/test.txt'],
            ['test/test 1.txt'],
            ['test  1/test  2.txt'],
        ];
    }
}
