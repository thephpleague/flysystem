<?php

declare(strict_types=1);

namespace League\Flysystem\WebDAV;

use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\Config;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\Visibility;

abstract class WebDAVAdapterTestCase extends FilesystemAdapterTestCase
{
    /**
     * @test
     */
    public function setting_visibility(): void
    {
        $adapter = $this->adapter();
        $this->givenWeHaveAnExistingFile('some/file.txt');

        $this->expectException(UnableToSetVisibility::class);

        $adapter->setVisibility('some/file.txt', Visibility::PRIVATE);
    }

    /**
     * @test
     */
    public function overwriting_a_file(): void
    {
        $this->runScenario(function () {
            $this->givenWeHaveAnExistingFile('path.txt', 'contents');
            $adapter = $this->adapter();

            $adapter->write('path.txt', 'new contents', new Config());

            $contents = $adapter->read('path.txt');
            $this->assertEquals('new contents', $contents);
        });
    }

    /**
     * @test
     */
    public function creating_a_directory_with_leading_and_trailing_slashes(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->createDirectory('/some/directory/', new Config);

            self::assertTrue($adapter->directoryExists('/some/directory/'));
        });
    }

    /**
     * @test
     */
    public function copying_a_file(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write(
                'source.txt',
                'contents to be copied',
                new Config()
            );

            $adapter->copy('source.txt', 'destination.txt', new Config());

            $this->assertTrue($adapter->fileExists('source.txt'));
            $this->assertTrue($adapter->fileExists('destination.txt'));
            $this->assertEquals('contents to be copied', $adapter->read('destination.txt'));
        });
    }

    /**
     * @test
     */
    public function copying_a_file_again(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write(
                'source.txt',
                'contents to be copied',
                new Config()
            );

            $adapter->copy('source.txt', 'destination.txt', new Config());

            $this->assertTrue($adapter->fileExists('source.txt'));
            $this->assertTrue($adapter->fileExists('destination.txt'));
            $this->assertEquals('contents to be copied', $adapter->read('destination.txt'));
        });
    }

    /**
     * @test
     */
    public function moving_a_file(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write(
                'source.txt',
                'contents to be copied',
                new Config()
            );
            $adapter->move('source.txt', 'destination.txt', new Config());
            $this->assertFalse(
                $adapter->fileExists('source.txt'),
                'After moving a file should no longer exist in the original location.'
            );
            $this->assertTrue(
                $adapter->fileExists('destination.txt'),
                'After moving, a file should be present at the new location.'
            );
            $this->assertEquals('contents to be copied', $adapter->read('destination.txt'));
        });
    }

    /**
     * @test
     */
    public function moving_a_file_that_does_not_exist(): void
    {
        $this->expectException(UnableToMoveFile::class);

        $this->runScenario(function () {
            $this->adapter()->move('source.txt', 'destination.txt', new Config());
        });
    }
}
