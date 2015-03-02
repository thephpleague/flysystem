<?php

namespace League\Flysystem\Test\Adapter\Polyfill;

use League\Flysystem\Config;

/**
 * A trait to write fixtures for adapter tests, using the adapter itself.
 *
 * Use this if you do not need to mock the storage backend. You probably want
 * to provide a teardown() method in your test to remove the created files
 * again.
 */
trait WriteFixturesTrait
{
    /**
     * Make sure the adapter sees a directory with the specified listing.
     *
     * @param string $dirname
     * @param array  $listing List of file names
     */
    protected function ensureDirectoryContainsListing($dirname, array $listing)
    {
        $this->ensureDirectoryExistsAtLocation($dirname);
        foreach($listing as $file) {
            $this->ensureFileExistsAtLocation($dirname.'/'.$file, 'dummy');
        }
    }

    /**
     * Make sure the adapter sees a file at the location with the specified content.
     *
     * @param string  $location
     * @param string  $contents
     * @param boolean $private  Whether the file should be private.
     *
     * @throw \PHPUnit_Framework_IncompleteTestError if private files are not supported.
     */
    protected function ensureFileExistsAtLocation($location, $contents, $private = false)
    {
        $this->adapter->write($location, $contents, new Config());
        if ($private) {
            $this->adapter->setVisibility($location, 'private');
        }
    }

    /**
     * Make sure the adapter sees a directory at the location.
     *
     * @param string $location
     */
    protected function ensureDirectoryExistsAtLocation($location)
    {
        $this->adapter->createDir($location, new Config());
    }
}
