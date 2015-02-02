<?php

namespace League\Flysystem\Test\Adapter;

use League\Flysystem\AdapterInterface;
use League\Flysystem\ReadInterface;
use League\Flysystem\Config;

/**
 * Base test case for reading adapters
 *
 * Extend for your adapter, overwrite the setup<Type> methods to change how
 * fixtures are prepared.
 */
abstract class ReadTestCase extends \PHPUnit_Framework_TestCase
{
    const FILE_CONTENT = 'file content';

    /**
     * @var ReadInterface
     */
    protected $adapter;

    /**
     * @var string
     */
    protected $root;

    public function setUp()
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
     * @return ReadInterface
     */
    abstract protected function getAdapter($root);

    /**
     * Make sure the adapter sees a directory with the specified listing.
     *
     * @param string $dirname
     * @param array  $listing List of file names
     */
    abstract protected function ensureDirectoryContainsListing($dirname, array $listing);

    /**
     * Make sure the adapter sees a file at the location with the specified content.
     *
     * @param string  $location
     * @param string  $contents
     * @param boolean $private  Whether the file should be private.
     *
     * @throw \PHPUnit_Framework_IncompleteTestError if private files are not supported.
     */
    abstract protected function ensureFileExistsAtLocation($location, $contents, $private = false);

    /**
     * Make sure the adapter sees a directory at the location.
     *
     * @param string $location
     */
    abstract protected function ensureDirectoryExistsAtLocation($location);

    /**
     * Ensure that $result is an array that has key path with value $path.
     */
    protected function assertPath($path, $result)
    {
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('path', $result);
        $this->assertEquals($path, $result['path']);
    }

    /**
     * Ensure that $result is an array that has key type with value $type.
     */
    protected function assertType($type, $result)
    {
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals($type, $result['type']);
    }

    /**
     * Ensure that $result is an array that has key size with value $size.
     */
    protected function assertSize($size, $result)
    {
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('size', $result);
        $this->assertEquals($size, $result['size']);
    }

    /**
     * Ensure that $result is an array that has a key timestamp with an integer in it.
     */
    protected function assertHasTimestamp($result)
    {
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertInternalType('int', $result['timestamp']);
    }

    /**
     * Ensure that $result is an array that has key mmimetype with value $mimetype.
     */
    protected function assertMimeType($mimetype, $result)
    {
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('mimetype', $result);
        $this->assertEquals($mimetype, $result['mimetype']);
    }

    public function testHasWithDir()
    {
        $this->ensureDirectoryExistsAtLocation('0');
        $this->assertTrue($this->adapter->has('0'));
    }

    public function testHasWithFile()
    {
        $this->ensureFileExistsAtLocation('file.txt', static::FILE_CONTENT);
        $this->assertTrue($this->adapter->has('file.txt'));
    }

    public function testRead()
    {
        $this->ensureFileExistsAtLocation('file.txt', static::FILE_CONTENT);
        $result = $this->adapter->read('file.txt');
        $this->assertPath('file.txt', $result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('contents', $result);
        $this->assertEquals(static::FILE_CONTENT, $result['contents']);
    }

    public function testReadStream()
    {
        $this->ensureFileExistsAtLocation('file.txt', static::FILE_CONTENT);
        $result = $this->adapter->readStream('file.txt');
        $this->assertPath('file.txt', $result);
        $this->assertArrayHasKey('stream', $result);
        $this->assertInternalType('resource', $result['stream']);
        $this->assertEquals(static::FILE_CONTENT, fread($result['stream'], strlen(static::FILE_CONTENT)));
        fclose($result['stream']);
    }

    public function testListingNonexistingDirectory()
    {
        $result = $this->adapter->listContents('nonexisting/directory');
        $this->assertEquals([], $result);
    }

    public function testListContents()
    {
        $this->ensureDirectoryContainsListing('0', array('thing', 'file.txt'));
        $contents = $this->adapter->listContents('0', false);
        $this->assertCount(2, $contents);
        $this->assertArrayHasKey('type', $contents[0]);
        $this->assertArrayHasKey('type', $contents[1]);
    }

    public function testGetMetadata()
    {
        $this->ensureFileExistsAtLocation('file.txt', static::FILE_CONTENT);
        $result = $this->adapter->getMetadata('file.txt');
        $this->assertType('file', $result);
        $this->assertPath('file.txt', $result);
        $this->assertSize(strlen(static::FILE_CONTENT), $result);
        $this->assertHasTimestamp($result);
        // no mimetype in default metadata set
    }

    public function testGetSize()
    {
        $this->ensureFileExistsAtLocation('file.txt', static::FILE_CONTENT);
        $result = $this->adapter->getSize('file.txt');
        $this->assertSize(strlen(static::FILE_CONTENT), $result);
    }

    public function testGetTimestamp()
    {
        $this->ensureFileExistsAtLocation('file.txt', static::FILE_CONTENT);
        $result = $this->adapter->getTimestamp('file.txt');
        $this->assertHasTimestamp($result);
    }

    public function testGetMimetype()
    {
        $this->ensureFileExistsAtLocation('file.txt', static::FILE_CONTENT);
        $result = $this->adapter->getMimetype('file.txt');
        $this->assertMimeType('text/plain', $result);
    }

    public function testVisibilityPublic()
    {
        if (IS_WINDOWS) {
            $this->markTestSkipped("Visibility not supported on Windows.");
        }
        if (in_array('League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait', class_uses($this->adapter))) {
            $this->setExpectedException('\LogicException');
        }

        $this->ensureFileExistsAtLocation('file.txt', static::FILE_CONTENT);

        $output = $this->adapter->getVisibility('file.txt');
        $this->assertInternalType('array', $output);
        $this->assertArrayHasKey('visibility', $output);
        $this->assertEquals('public', $output['visibility']);
    }

    public function testVisibilityPrivate()
    {
        if (IS_WINDOWS) {
            $this->markTestSkipped("Visibility not supported on Windows.");
        }
        if (in_array('League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait', class_uses($this->adapter))) {
            $this->setExpectedException('\LogicException');
        }

        $this->ensureFileExistsAtLocation('private.txt', static::FILE_CONTENT, true);

        $output = $this->adapter->getVisibility('private.txt');
        $this->assertInternalType('array', $output);
        $this->assertArrayHasKey('visibility', $output);
        $this->assertEquals('private', $output['visibility']);
    }
}
