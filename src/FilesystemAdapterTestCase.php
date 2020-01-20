<?php

declare(strict_types=1);

namespace League\Flysystem;

use Generator;
use PHPUnit\Framework\TestCase;
use Throwable;

abstract class FilesystemAdapterTestCase extends TestCase
{
    /**
     * @var FilesystemAdapter
     */
    private $adapter;

    abstract protected function createFilesystemAdapter(): FilesystemAdapter;

    public function adapter(): FilesystemAdapter
    {
        if ( ! $this->adapter instanceof FilesystemAdapter) {
            $this->adapter = $this->createFilesystemAdapter();
        }

        return $this->adapter;
    }

    /**
     * @before
     */
    public function clearStorage(): void
    {
        try {
            $adapter = $this->adapter();
        } catch (Throwable $exception) {
            /**
             * Setting up the filesystem adapter failed. This is OK at this stage.
             * The exception will have been shown to the user when trying to run
             * a test. We expect an exception to be thrown when tests are marked as
             * skipped when a filesystem adapter cannot be constructed.
             */
            return;
        }

        /** @var StorageAttributes $item */
        foreach ($adapter->listContents('/', false) as $item) {
            if ($item->isDir()) {
                $adapter->deleteDirectory($item->path());
            } else {
                $adapter->delete($item->path());
            }
        }
    }

    /**
     * @test
     */
    public function writing_and_reading_with_string()
    {
        $adapter = $this->adapter();

        $adapter->write('path.txt', 'contents', new Config());
        $fileExists = $adapter->fileExists('path.txt');
        $contents = $adapter->read('path.txt');

        $this->assertTrue($fileExists);
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function writing_a_file_with_a_stream()
    {
        $adapter = $this->adapter();
        $writeStream = stream_with_contents('contents');

        $adapter->writeStream('path.txt', $writeStream, new Config());
        $fileExists = $adapter->fileExists('path.txt');

        $this->assertTrue($fileExists);
    }

    /**
     * @test
     */
    public function reading_a_file()
    {
        $this->givenWeHaveAnExistingFile('path.txt', 'contents');

        $contents = $this->adapter()->read('path.txt');

        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function reading_a_file_with_a_stream()
    {
        $this->givenWeHaveAnExistingFile('path.txt', 'contents');

        $readStream = $this->adapter()->readStream('path.txt');
        $contents = stream_get_contents($readStream);

        $this->assertIsResource($readStream);
        $this->assertEquals('contents', $contents);
        fclose($readStream);
    }

    /**
     * @test
     */
    public function deleting_a_file()
    {
        $adapter = $this->adapter();
        $this->givenWeHaveAnExistingFile('path.txt', 'contents');

        $adapter->delete('path.txt');
        $fileExists = $adapter->fileExists('path.txt');

        $this->assertFalse($fileExists);
    }

    /**
     * @test
     */
    public function listing_contents_shallow()
    {
        $this->givenWeHaveAnExistingFile('some/0-path.txt', 'contents');
        $this->givenWeHaveAnExistingFile('some/1-nested/path.txt', 'contents');

        $listing = $this->adapter()->listContents('some', false);
        /** @var StorageAttributes[] $items */
        $items = iterator_to_array($listing);

        $this->assertInstanceOf(Generator::class, $listing);
        $this->assertContainsOnlyInstancesOf(StorageAttributes::class, $items);
        $this->assertCount(2, $items);

        // Order of entries is not guaranteed
        [$fileIndex, $directoryIndex] = $items[0]->isFile() ? [0, 1] : [1, 0];

        $this->assertEquals('some/0-path.txt', $items[$fileIndex]->path());
        $this->assertEquals('some/1-nested', $items[$directoryIndex]->path());
        $this->assertTrue($items[$fileIndex]->isFile());
        $this->assertTrue($items[$directoryIndex]->isDir());
    }

    protected function givenWeHaveAnExistingFile(string $path, string $contents, array $config = [])
    {
        $this->adapter()->write($path, $contents, new Config($config));
    }

    /**
     * @test
     */
    public function fetching_file_size()
    {
        $adapter = $this->adapter();
        $this->givenWeHaveAnExistingFile('path.txt', 'contents');

        $attributes = $adapter->fileSize('path.txt');

        $this->assertInstanceOf(FileAttributes::class, $attributes);
        $this->assertEquals(8, $attributes->fileSize());
    }

    /**
     * @test
     */
    public function setting_visibility()
    {
        $adapter = $this->adapter();
        $this->givenWeHaveAnExistingFile('some/path.txt', 'contents', [Config::OPTION_VISIBILITY => Visibility::PUBLIC]);
        $this->assertEquals(Visibility::PUBLIC, $adapter->visibility('some/path.txt')->visibility());
        $adapter->setVisibility('some/path.txt', Visibility::PRIVATE);
        $this->assertEquals(Visibility::PRIVATE, $adapter->visibility('some/path.txt')->visibility());
        $adapter->setVisibility('some/path.txt', Visibility::PUBLIC);
        $this->assertEquals(Visibility::PUBLIC, $adapter->visibility('some/path.txt')->visibility());
    }

    /**
     * @test
     */
    public function fetching_file_size_of_a_directory()
    {
        $this->expectException(UnableToRetrieveMetadata::class);

        $adapter = $this->adapter();
        $adapter->createDirectory('path', new Config());

        $adapter->fileSize('path/');
    }

    /**
     * @test
     */
    public function fetching_file_size_of_non_existing_file()
    {
        $this->expectException(UnableToRetrieveMetadata::class);

        $this->adapter()->fileSize('non-existing-file.txt');
    }

    /**
     * @test
     */
    public function fetching_last_modified_of_non_existing_file()
    {
        $this->expectException(UnableToRetrieveMetadata::class);

        $this->adapter()->lastModified('non-existing-file.txt');
    }

    /**
     * @test
     */
    public function fetching_mime_type_of_non_existing_file()
    {
        $this->expectException(UnableToRetrieveMetadata::class);

        $this->adapter()->mimeType('non-existing-file.txt');
    }

    /**
     * @test
     */
    public function updating_and_reading()
    {
        $adapter = $this->adapter();

        $adapter->update('some/path.txt', 'contents', new Config());

        $contents = $adapter->read('some/path.txt');
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function writing_and_reading_with_streams()
    {
        $writeStream = stream_with_contents('contents');
        $adapter = $this->adapter();

        $adapter->writeStream('path.txt', $writeStream, new Config());
        fclose($writeStream);
        $readStream = $adapter->readStream('path.txt');

        $this->assertIsResource($readStream);
        $contents = stream_get_contents($readStream);
        fclose($readStream);
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function setting_visibility_on_a_file_that_does_not_exist()
    {
        $this->expectException(UnableToSetVisibility::class);
        $this->adapter()->setVisibility('path.txt', Visibility::PRIVATE);
    }

    /**
     * @test
     */
    public function copying_a_file()
    {
        $adapter = $this->adapter();
        $adapter->write(
            'source.txt',
            'contents to be copied',
            new Config([Config::OPTION_VISIBILITY => Visibility::PUBLIC])
        );

        $adapter->copy('source.txt', 'destination.txt', new Config());

        $this->assertTrue($adapter->fileExists('source.txt'));
        $this->assertTrue($adapter->fileExists('destination.txt'));
        $this->assertEquals(Visibility::PUBLIC, $adapter->visibility('destination.txt')->visibility());
        $this->assertEquals('contents to be copied', $adapter->read('destination.txt'));
    }

    /**
     * @test
     */
    public function moving_a_file()
    {
        $adapter = $this->adapter();
        $adapter->write(
            'source.txt',
            'contents to be copied',
            new Config([Config::OPTION_VISIBILITY => Visibility::PUBLIC])
        );
        $adapter->move('source.txt', 'destination.txt', new Config());
        $this->assertFalse($adapter->fileExists('source.txt'));
        $this->assertTrue($adapter->fileExists('destination.txt'));
        $this->assertEquals(Visibility::PUBLIC, $adapter->visibility('destination.txt')->visibility());
        $this->assertEquals('contents to be copied', $adapter->read('destination.txt'));
    }

    /**
     * @test
     */
    public function reading_a_file_that_does_not_exist()
    {
        $this->expectException(UnableToReadFile::class);
        $this->adapter()->read('path.txt');
    }

    /**
     * @test
     */
    public function moving_a_file_that_does_not_exist()
    {
        $this->expectException(UnableToMoveFile::class);
        $this->adapter()->move('source.txt', 'destination.txt', new Config());
    }

    /**
     * @test
     */
    public function trying_to_delete_a_non_existing_file()
    {
        $adapter = $this->adapter();

        $adapter->delete('path.txt');
        $fileExists = $adapter->fileExists('path.txt');

        $this->assertFalse($fileExists);
    }

    /**
     * @test
     */
    public function checking_if_files_exist()
    {
        $adapter = $this->adapter();

        $fileExistsBefore = $adapter->fileExists('some/path.txt');
        $adapter->write('some/path.txt', 'contents', new Config());
        $fileExistsAfter = $adapter->fileExists('some/path.txt');

        $this->assertFalse($fileExistsBefore);
        $this->assertTrue($fileExistsAfter);
    }

    /**
     * @test
     */
    public function updating_and_reading_with_streams()
    {
        $writeStream = stream_with_contents('contents');
        $adapter = $this->adapter();

        $adapter->updateStream('path.txt', $writeStream, new Config());
        fclose($writeStream);
        $readStream = $adapter->readStream('path.txt');

        $this->assertIsResource($readStream);
        $contents = stream_get_contents($readStream);
        fclose($readStream);
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function fetching_last_modified()
    {
        $adapter = $this->adapter();
        $adapter->write('path.txt', 'contents', new Config());

        $attributes = $adapter->lastModified('path.txt');

        $this->assertInstanceOf(FileAttributes::class, $attributes);
        $this->assertIsInt($attributes->lastModified());
        $this->assertTrue($attributes->lastModified() > time() - 30);
        $this->assertTrue($attributes->lastModified() < time() + 30);
    }

    /**
     * @test
     */
    public function creating_a_directory()
    {
        $adapter = $this->adapter();

        $adapter->createDirectory('path', new Config());

        $contents = iterator_to_array($adapter->listContents('', false));
        $this->assertCount(1, $contents);
        /** @var DirectoryAttributes $directory */
        $directory = $contents[0];
        $this->assertInstanceOf(DirectoryAttributes::class, $directory);
        $this->assertEquals('path', $directory->path());
    }

    protected function assertFileExistsAtPath(string $path): void
    {
        $fileExists = $this->adapter()->fileExists($path);
        $this->assertTrue($fileExists);
    }
}
