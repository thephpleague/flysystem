<?php

declare(strict_types=1);

namespace League\Flysystem\AdapterTestUtilities;

use Generator;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\Visibility;
use PHPUnit\Framework\TestCase;
use Throwable;

use const PHP_EOL;

/**
 * @codeCoverageIgnore
 */
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
     * @after
     */
    public function clearStorage(): void
    {
        reset_function_mocks();

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
        foreach ($adapter->listContents('', false) as $item) {
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
    public function writing_and_reading_with_string(): void
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
    public function writing_a_file_with_a_stream(): void
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
    public function reading_a_file(): void
    {
        $this->givenWeHaveAnExistingFile('path.txt', 'contents');

        $contents = $this->adapter()->read('path.txt');

        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function reading_a_file_with_a_stream(): void
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
    public function deleting_a_file(): void
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
    public function listing_contents_shallow(): void
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

    /**
     * @test
     */
    public function listing_contents_recursive(): void
    {
        $adapter = $this->adapter();
        $adapter->createDirectory('path', new Config());
        $adapter->write('path/file.txt', 'string', new Config());

        $listing = $adapter->listContents('', true);
        /** @var StorageAttributes[] $items */
        $items = iterator_to_array($listing);
        $this->assertCount(2, $items, $this->formatIncorrectListingCount($items));
    }

    protected function formatIncorrectListingCount(array $items): string
    {
        $message = "Incorrect number of items returned.\nThe listing contains:\n\n";

        /** @var StorageAttributes $item */
        foreach ($items as $item) {
            $message .= "- {$item->path()}\n";
        }

        return $message . PHP_EOL;
    }

    protected function givenWeHaveAnExistingFile(string $path, string $contents = 'contents', array $config = []): void
    {
        $this->adapter()->write($path, $contents, new Config($config));
    }

    /**
     * @test
     */
    public function fetching_file_size(): void
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
    public function setting_visibility(): void
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
    public function fetching_file_size_of_a_directory(): void
    {
        $this->expectException(UnableToRetrieveMetadata::class);

        $adapter = $this->adapter();
        $adapter->createDirectory('path', new Config());

        $adapter->fileSize('path/');
    }

    /**
     * @test
     */
    public function fetching_file_size_of_non_existing_file(): void
    {
        $this->expectException(UnableToRetrieveMetadata::class);

        $this->adapter()->fileSize('non-existing-file.txt');
    }

    /**
     * @test
     */
    public function fetching_last_modified_of_non_existing_file(): void
    {
        $this->expectException(UnableToRetrieveMetadata::class);

        $this->adapter()->lastModified('non-existing-file.txt');
    }

    /**
     * @test
     */
    public function fetching_visibility_of_non_existing_file(): void
    {
        $this->expectException(UnableToRetrieveMetadata::class);

        $this->adapter()->visibility('non-existing-file.txt');
    }

    /**
     * @test
     */
    public function fetching_the_mime_type_of_an_svg_file(): void
    {
        $this->givenWeHaveAnExistingFile('file.svg', file_get_contents(__DIR__.'/test_files/flysystem.svg'));

        $mimetype = $this->adapter()->mimeType('file.svg')->mimeType();

        $this->assertEquals('image/svg', $mimetype);
    }

    /**
     * @test
     */
    public function fetching_mime_type_of_non_existing_file(): void
    {
        $this->expectException(UnableToRetrieveMetadata::class);

        $this->adapter()->mimeType('non-existing-file.txt');
    }

    /**
     * @test
     */
    public function writing_and_reading_with_streams(): void
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
    public function setting_visibility_on_a_file_that_does_not_exist(): void
    {
        $this->expectException(UnableToSetVisibility::class);
        $this->adapter()->setVisibility('path.txt', Visibility::PRIVATE);
    }

    /**
     * @test
     */
    public function copying_a_file(): void
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
    public function moving_a_file(): void
    {
        $adapter = $this->adapter();
        $adapter->write(
            'source.txt',
            'contents to be copied',
            new Config([Config::OPTION_VISIBILITY => Visibility::PUBLIC])
        );
        $adapter->move('source.txt', 'destination.txt', new Config());
        $this->assertFalse($adapter->fileExists('source.txt'), 'After moving a file should no longer exist in the original location.');
        $this->assertTrue($adapter->fileExists('destination.txt'), 'After moving, a file should be present at the new location.');
        $this->assertEquals(Visibility::PUBLIC, $adapter->visibility('destination.txt')->visibility());
        $this->assertEquals('contents to be copied', $adapter->read('destination.txt'));
    }

    /**
     * @test
     */
    public function reading_a_file_that_does_not_exist(): void
    {
        $this->expectException(UnableToReadFile::class);
        $this->adapter()->read('path.txt');
    }

    /**
     * @test
     */
    public function moving_a_file_that_does_not_exist(): void
    {
        $this->expectException(UnableToMoveFile::class);
        $this->adapter()->move('source.txt', 'destination.txt', new Config());
    }

    /**
     * @test
     */
    public function trying_to_delete_a_non_existing_file(): void
    {
        $adapter = $this->adapter();

        $adapter->delete('path.txt');
        $fileExists = $adapter->fileExists('path.txt');

        $this->assertFalse($fileExists);
    }

    /**
     * @test
     */
    public function checking_if_files_exist(): void
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
    public function fetching_last_modified(): void
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
    public function creating_a_directory(): void
    {
        $adapter = $this->adapter();

        $adapter->createDirectory('path', new Config());

        // Creating a directory should be idempotent.
        $adapter->createDirectory('path', new Config());

        $contents = iterator_to_array($adapter->listContents('', false));
        $this->assertCount(1, $contents, $this->formatIncorrectListingCount($contents));
        /** @var DirectoryAttributes $directory */
        $directory = $contents[0];
        $this->assertInstanceOf(DirectoryAttributes::class, $directory);
        $this->assertEquals('path', $directory->path());
    }

    /**
     * @test
     */
    public function copying_a_file_with_collision(): void
    {
        $adapter = $this->adapter();
        $adapter->write('path.txt', 'new contents', new Config());
        $adapter->write('new-path.txt', 'contents', new Config());

        $adapter->copy('path.txt', 'new-path.txt', new Config());
        $contents = $adapter->read('new-path.txt');

        $this->assertEquals('new contents', $contents);
    }

    protected function assertFileExistsAtPath(string $path): void
    {
        $fileExists = $this->adapter()->fileExists($path);
        $this->assertTrue($fileExists);
    }
}
