<?php

declare(strict_types=1);

namespace League\Flysystem\InMemory;

use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
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
    public function setting_visibility_on_a_non_existing_file()
    {
        $this->expectException(UnableToSetVisibility::class);
        $this->adapter->setVisibility('path.txt', Visibility::PRIVATE);
    }

    /**
     * @test
     */
    public function getting_visibility_on_a_non_existing_file()
    {
        $this->expectException(UnableToRetrieveMetadata::class);
        $this->adapter->visibility('path.txt');
    }

    /**
     * @test
     */
    public function getting_mimetype_on_a_non_existing_file()
    {
        $this->expectException(UnableToRetrieveMetadata::class);
        $this->adapter->mimeType('path.txt');
    }

    /**
     * @test
     */
    public function getting_last_modified_on_a_non_existing_file()
    {
        $this->expectException(UnableToRetrieveMetadata::class);
        $this->adapter->lastModified('path.txt');
    }

    /**
     * @test
     */
    public function getting_file_size_on_a_non_existing_file()
    {
        $this->expectException(UnableToRetrieveMetadata::class);
        $this->adapter->fileSize('path.txt');
    }

    /**
     * @test
     */
    public function deleting_a_file()
    {
        $this->adapter->write('path.txt', 'contents', new Config());
        $this->assertTrue($this->adapter->fileExists('path.txt'));
        $this->adapter->delete('path.txt');
        $this->assertFalse($this->adapter->fileExists('path.txt'));
    }



    /**
     * @test
     */
    public function deleting_a_directory()
    {
        $this->adapter->write('a/path.txt', 'contents', new Config());
        $this->adapter->write('a/b/path.txt', 'contents', new Config());
        $this->adapter->write('a/b/c/path.txt', 'contents', new Config());
        $this->assertTrue($this->adapter->fileExists('a/b/path.txt'));
        $this->assertTrue($this->adapter->fileExists('a/b/c/path.txt'));
        $this->adapter->deleteDirectory('a/b');
        $this->assertTrue($this->adapter->fileExists('a/path.txt'));
        $this->assertFalse($this->adapter->fileExists('a/b/path.txt'));
        $this->assertFalse($this->adapter->fileExists('a/b/c/path.txt'));
    }

    /**
     * @test
     */
    public function creating_a_directory_does_nothing()
    {
        $this->adapter->createDirectory('something', new Config());
        $this->assertTrue(true);
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
    public function listing_all_files()
    {
        $this->adapter->write('path.txt', 'contents', new Config());
        $this->adapter->write('a/path.txt', 'contents', new Config());
        $this->adapter->write('a/b/path.txt', 'contents', new Config());;
        $listing = iterator_to_array($this->adapter->listContents('/', true));
        $this->assertCount(5, $listing);
        $this->assertContainsEquals(new FileAttributes('/path.txt'), $listing);
        $this->assertContainsEquals(new FileAttributes('/a/path.txt'), $listing);
        $this->assertContainsEquals(new FileAttributes('/a/b/path.txt'), $listing);
        $this->assertContainsEquals(new DirectoryAttributes('/a/'), $listing);
        $this->assertContainsEquals(new DirectoryAttributes('/a/b/'), $listing);
    }

    /**
     * @test
     */
    public function listing_non_recursive()
    {
        $this->adapter->write('path.txt', 'contents', new Config());
        $this->adapter->write('a/path.txt', 'contents', new Config());
        $this->adapter->write('a/b/path.txt', 'contents', new Config());
        $listing = iterator_to_array($this->adapter->listContents('/', false));
        $this->assertCount(2, $listing);
    }

    /**
     * @test
     */
    public function moving_a_file_successfully()
    {
        $this->adapter->write('path.txt', 'contents', new Config());
        $this->adapter->move('path.txt', 'new-path.txt', new Config());
        $this->assertFalse($this->adapter->fileExists('path.txt'));
        $this->assertTrue($this->adapter->fileExists('new-path.txt'));
    }

    /**
     * @test
     */
    public function moving_a_file_with_collision()
    {
        $this->expectException(UnableToMoveFile::class);
        $this->adapter->write('path.txt', 'contents', new Config());
        $this->adapter->write('new-path.txt', 'contents', new Config());
        $this->adapter->move('path.txt', 'new-path.txt', new Config());
    }

    /**
     * @test
     */
    public function trying_to_move_a_non_existing_file()
    {
        $this->expectException(UnableToMoveFile::class);
        $this->adapter->move('path.txt', 'new-path.txt', new Config());
    }

    /**
     * @test
     */
    public function copying_a_file_successfully()
    {
        $this->adapter->write('path.txt', 'contents', new Config());
        $this->adapter->copy('path.txt', 'new-path.txt', new Config());
        $this->assertTrue($this->adapter->fileExists('path.txt'));
        $this->assertTrue($this->adapter->fileExists('new-path.txt'));
    }

    /**
     * @test
     */
    public function trying_to_copy_a_non_existing_file()
    {
        $this->expectException(UnableToCopyFile::class);
        $this->adapter->copy('path.txt', 'new-path.txt', new Config());
    }

    /**
     * @test
     */
    public function copying_a_file_with_collision()
    {
        $this->expectException(UnableToCopyFile::class);
        $this->adapter->write('path.txt', 'contents', new Config());
        $this->adapter->write('new-path.txt', 'contents', new Config());
        $this->adapter->copy('path.txt', 'new-path.txt', new Config());
    }

    /**
     * @test
     */
    public function checking_for_metadata()
    {
        mock_function('time', 1234);
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
