<?php

declare(strict_types=1);

namespace League\Flysystem\InMemory;

use League\Flysystem\Config;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\Visibility;
use PHPUnit\Framework\TestCase;

class InMemoryFilesystemTest extends TestCase
{
    const PATH = 'path.txt';

    /**
     * @var InMemoryFilesystem
     */
    private $adapter;

    protected function setUp(): void
    {
        $this->adapter = new InMemoryFilesystem();
    }

    /**
     * @test
     */
    public function writing_and_reading_a_file()
    {
        $this->adapter->write(self::PATH, 'contents', new Config());
        $contents = $this->adapter->read(self::PATH);
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function setting_visibility()
    {
        $this->adapter->write(self::PATH, 'contents', new Config(['visibility' => Visibility::PRIVATE]));
        $contents = $this->adapter->visibility(self::PATH);
        $this->assertEquals(Visibility::PRIVATE, $contents);
        $this->adapter->setVisibility(self::PATH, Visibility::PUBLIC);
        $contents = $this->adapter->visibility(self::PATH);
        $this->assertEquals(Visibility::PUBLIC, $contents);
    }

    /**
     * @test
     */
    public function updating_and_reading_a_file()
    {
        $this->adapter->update(self::PATH, 'contents', new Config());
        $contents = $this->adapter->read(self::PATH);
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function writing_with_a_stream_and_reading_a_file()
    {
        $handle = stream_with_contents('contents');
        $this->adapter->writeStream(self::PATH, $handle, new Config());
        $contents = $this->adapter->read(self::PATH);
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function updating_with_a_stream_and_reading_a_file()
    {
        $handle = stream_with_contents('contents');
        $this->adapter->updateStream(self::PATH, $handle, new Config());
        fclose($handle);
        $contents = $this->adapter->read(self::PATH);
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function reading_a_stream()
    {
        $this->adapter->write(self::PATH, 'contents', new Config());
        $contents = $this->adapter->readStream(self::PATH);
        $this->assertEquals('contents', stream_get_contents($contents));
        fclose($contents);
    }

    /**
     * @test
     */
    public function reading_a_non_existing_file()
    {
        $this->expectException(UnableToReadFile::class);
        $this->adapter->read('path.txt');
    }

    /**
     * @test
     */
    public function stream_reading_a_non_existing_file()
    {
        $this->expectException(UnableToReadFile::class);
        $this->adapter->readStream('path.txt');
    }

    /**
     * @test
     */
    public function checking_for_metadata()
    {
        mock_function('time', 1234, 1234);
        $this->adapter->write(
            self::PATH,
            file_get_contents(__DIR__.'/../../test_files/flysystem.svg'),
            new Config()
        );

        $this->assertTrue($this->adapter->fileExists(self::PATH));
        $this->assertEquals(753, $this->adapter->fileSize(self::PATH));
        $this->assertEquals(1234, $this->adapter->lastModified(self::PATH));
        $this->assertEquals('image/svg', $this->adapter->mimeType(self::PATH));
    }
}
