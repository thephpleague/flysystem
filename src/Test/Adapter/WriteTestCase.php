<?php

namespace League\Flysystem\Test\Adapter;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

/**
 * Base test case for writing operations by adapters.
 *
 * Extend for your adapter, overwrite the setup<Type> methods to change how
 * fixtures are prepared.
 */
abstract class WriteTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var string
     */
    protected $root;

    public function setup()
    {
        $this->root = $this->getRoot();
        $this->adapter = $this->getAdapter($this->root);
    }

    /**
     * @return string The root directory to use.
     */
    abstract protected function getRoot();

    /**
     * Build the adapter for this test.
     *
     * @param string $root The root path.
     *
     * @return AdapterInterface
     */
    abstract protected function getAdapter($root);

    /**
     * Make sure the adapter sees a file at the location with the specified content.
     *
     * @param string  $location Path to the file.
     * @param string  $contents The content to write into the file.
     * @param boolean $private  Whether the file should be private.
     *
     * @throw \PHPUnit_Framework_IncompleteTestError if private files are not supported.
     */
    abstract protected function ensureFileExistsAtLocation($location, $contents, $private = false);

    /**
     * Check whether a file was created by the adapter and contains the expected data.
     *
     * @param string $location Path to the file.
     * @param string $contents Content of the file.
     */
    abstract protected function assertAdapterFileContains($location, $contents);

    /**
     * Check that $location exists and is a directory.
     *
     * @param string $location Path to the directory.
     */
    abstract protected function assertAdapterDirectoryExists($location);

    /**
     * Check that $location does not exist.
     *
     * @param string $location Path to the directory.
     */
    abstract protected function assertNotExist($location);

    public function testWriteStream()
    {
        $temp = tmpfile();
        fwrite($temp, 'dummy');
        rewind($temp);
        $this->adapter->writeStream('file.txt', $temp, new Config());
        fclose($temp);

        $this->assertAdapterFileContains('file.txt', 'dummy');

        $this->assertTrue($this->adapter->has('file.txt'));
        $result = $this->adapter->read('file.txt');
        $this->assertEquals('dummy', $result['contents']);

        $this->adapter->delete('file.txt');
    }

    public function testDeleteDir()
    {
        $this->ensureFileExistsAtLocation('nested/dir/path.txt', 'contents');
        $this->assertAdapterDirectoryExists('nested/dir');

        $this->adapter->deleteDir('nested');

        $this->assertNotExist('nested');
        $this->assertFalse($this->adapter->has('nested/dir/path.txt'));
        $this->assertFalse($this->adapter->has('nested/dir'));
        $this->assertFalse($this->adapter->has('nested'));
    }

    public function testUpdateStream()
    {
        $this->ensureFileExistsAtLocation('file.txt', 'old');
        $temp = tmpfile();
        fwrite($temp, 'updated');
        rewind($temp);
        $this->adapter->updateStream('file.txt', $temp, new Config());
        fclose($temp);

        $this->assertAdapterFileContains('file.txt', 'updated');

        $this->assertTrue($this->adapter->has('file.txt'));
        $result = $this->adapter->read('file.txt');
        $this->assertEquals('updated', $result['contents']);

        $this->adapter->delete('file.txt');
    }

    public function testCreateZeroDir()
    {
        $this->adapter->createDir('0', new Config());

        $this->assertAdapterDirectoryExists('0');

        $this->adapter->deleteDir('0');
    }

    public function testCopy()
    {
        $this->ensureFileExistsAtLocation('file.txt', 'data');
        $this->assertTrue($this->adapter->copy('file.txt', 'new.ext'));
        $this->assertTrue($this->adapter->has('new.ext'));
        $this->assertAdapterFileContains('file.txt', 'data');

        $this->adapter->delete('file.txt');
        $this->adapter->delete('new.ext');
    }

    public function testRenameToNonExistsingDirectory()
    {
        $this->ensureFileExistsAtLocation('file.txt', 'contents');
        $dirname = uniqid();
        $this->assertNotExist($dirname);

        $this->assertTrue($this->adapter->rename('file.txt', $dirname.'/file.txt'));

        $this->assertAdapterFileContains($dirname.'/file.txt', 'contents');

        $this->adapter->deleteDir($dirname);
    }
}
