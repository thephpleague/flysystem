<?php

declare(strict_types=1);

namespace League\Flysystem\AdapterTestUtilities;

use const PHP_EOL;
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
use function file_get_contents;

/**
 * @codeCoverageIgnore
 */
abstract class FilesystemAdapterTestCase extends TestCase
{
    use RetryOnTestException;

    /**
     * @var FilesystemAdapter
     */
    protected static $adapter;

    /**
     * @var bool
     */
    private $isUsingCustomAdapter = false;

    public static function clearFilesystemAdapterCache(): void
    {
        static::$adapter = null;
    }

    abstract protected static function createFilesystemAdapter(): FilesystemAdapter;

    public function adapter(): FilesystemAdapter
    {
        if ( ! static::$adapter instanceof FilesystemAdapter) {
            static::$adapter = static::createFilesystemAdapter();
        }

        return static::$adapter;
    }

    public static function tearDownAfterClass(): void
    {
        self::clearFilesystemAdapterCache();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter();
    }

    protected function useAdapter(FilesystemAdapter $adapter): FilesystemAdapter
    {
        static::$adapter = $adapter;
        $this->isUsingCustomAdapter = true;

        return $adapter;
    }

    /**
     * @after
     */
    public function cleanupAdapter(): void
    {
        $this->clearStorage();
        $this->clearCustomAdapter();
    }

    public function clearStorage(): void
    {
        reset_function_mocks();

        try {
            $adapter = $this->adapter();
        } catch (Throwable $exception) {
            /*
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

    public function clearCustomAdapter(): void
    {
        if ($this->isUsingCustomAdapter) {
            $this->isUsingCustomAdapter = false;
            self::clearFilesystemAdapterCache();
        }
    }

    /**
     * @test
     */
    public function writing_and_reading_with_string(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();

            $adapter->write('path.txt', 'contents', new Config());
            $fileExists = $adapter->fileExists('path.txt');
            $contents = $adapter->read('path.txt');

            $this->assertTrue($fileExists);
            $this->assertEquals('contents', $contents);
        });
    }

    /**
     * @test
     */
    public function writing_a_file_with_a_stream(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $writeStream = stream_with_contents('contents');

            $adapter->writeStream('path.txt', $writeStream, new Config());
            fclose($writeStream);
            $fileExists = $adapter->fileExists('path.txt');

            $this->assertTrue($fileExists);
        });
    }

    /**
     * @test
     * @dataProvider filenameProvider
     */
    public function writing_and_reading_files_with_special_path(string $path): void
    {
        $this->runScenario(function () use ($path) {
            $adapter = $this->adapter();

            $adapter->write($path, 'contents', new Config());
            $contents = $adapter->read($path);

            $this->assertEquals('contents', $contents);
        });
    }

    public function filenameProvider(): Generator
    {
        yield "a path with square brackets in filename 1" => ["some/file[name].txt"];
        yield "a path with square brackets in filename 2" => ["some/file[0].txt"];
        yield "a path with square brackets in filename 3" => ["some/file[10].txt"];
        yield "a path with square brackets in dirname 1" => ["some[name]/file.txt"];
        yield "a path with square brackets in dirname 2" => ["some[0]/file.txt"];
        yield "a path with square brackets in dirname 3" => ["some[10]/file.txt"];
        yield "a path with curly brackets in filename 1" => ["some/file{name}.txt"];
        yield "a path with curly brackets in filename 2" => ["some/file{0}.txt"];
        yield "a path with curly brackets in filename 3" => ["some/file{10}.txt"];
        yield "a path with curly brackets in dirname 1" => ["some{name}/filename.txt"];
        yield "a path with curly brackets in dirname 2" => ["some{0}/filename.txt"];
        yield "a path with curly brackets in dirname 3" => ["some{10}/filename.txt"];
        yield "a path with space in dirname" => ["some dir/filename.txt"];
        yield "a path with space in filename" => ["somedir/file name.txt"];
    }

    /**
     * @test
     */
    public function writing_a_file_with_an_empty_stream(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $writeStream = stream_with_contents('');

            $adapter->writeStream('path.txt', $writeStream, new Config());
            fclose($writeStream);
            $fileExists = $adapter->fileExists('path.txt');

            $this->assertTrue($fileExists);

            $contents = $adapter->read('path.txt');
            $this->assertEquals('', $contents);
        });
    }

    /**
     * @test
     */
    public function reading_a_file(): void
    {
        $this->givenWeHaveAnExistingFile('path.txt', 'contents');

        $this->runScenario(function () {
            $contents = $this->adapter()->read('path.txt');

            $this->assertEquals('contents', $contents);
        });
    }

    /**
     * @test
     */
    public function reading_a_file_with_a_stream(): void
    {
        $this->givenWeHaveAnExistingFile('path.txt', 'contents');

        $this->runScenario(function () {
            $readStream = $this->adapter()->readStream('path.txt');
            $contents = stream_get_contents($readStream);

            $this->assertIsResource($readStream);
            $this->assertEquals('contents', $contents);
            fclose($readStream);
        });
    }

    /**
     * @test
     */
    public function overwriting_a_file(): void
    {
        $this->runScenario(function () {
            $this->givenWeHaveAnExistingFile('path.txt', 'contents', ['visibility' => Visibility::PUBLIC]);
            $adapter = $this->adapter();

            $adapter->write('path.txt', 'new contents', new Config(['visibility' => Visibility::PRIVATE]));

            $contents = $adapter->read('path.txt');
            $this->assertEquals('new contents', $contents);
            $visibility = $adapter->visibility('path.txt')->visibility();
            $this->assertEquals(Visibility::PRIVATE, $visibility);
        });
    }

    /**
     * @test
     */
    public function deleting_a_file(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $this->givenWeHaveAnExistingFile('path.txt', 'contents');

            $adapter->delete('path.txt');
            $fileExists = $adapter->fileExists('path.txt');

            $this->assertFalse($fileExists);
        });
    }

    /**
     * @test
     */
    public function listing_contents_shallow(): void
    {
        $this->runScenario(function () {
            $this->givenWeHaveAnExistingFile('some/0-path.txt', 'contents');
            $this->givenWeHaveAnExistingFile('some/1-nested/path.txt', 'contents');

            $listing = $this->adapter()->listContents('some', false);
            /** @var StorageAttributes[] $items */
            $items = iterator_to_array($listing);

            $this->assertInstanceOf(Generator::class, $listing);
            $this->assertContainsOnlyInstancesOf(StorageAttributes::class, $items);

            $this->assertCount(2, $items, $this->formatIncorrectListingCount($items));

            // Order of entries is not guaranteed
            [$fileIndex, $directoryIndex] = $items[0]->isFile() ? [0, 1] : [1, 0];

            $this->assertEquals('some/0-path.txt', $items[$fileIndex]->path());
            $this->assertEquals('some/1-nested', $items[$directoryIndex]->path());
            $this->assertTrue($items[$fileIndex]->isFile());
            $this->assertTrue($items[$directoryIndex]->isDir());
        });
    }

    /**
     * @test
     */
    public function listing_contents_recursive(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->createDirectory('path', new Config());
            $adapter->write('path/file.txt', 'string', new Config());

            $listing = $adapter->listContents('', true);
            /** @var StorageAttributes[] $items */
            $items = iterator_to_array($listing);
            $this->assertCount(2, $items, $this->formatIncorrectListingCount($items));
        });
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
        $this->runScenario(function () use ($path, $contents, $config) {
            $this->adapter()->write($path, $contents, new Config($config));
        });
    }

    /**
     * @test
     */
    public function fetching_file_size(): void
    {
        $adapter = $this->adapter();
        $this->givenWeHaveAnExistingFile('path.txt', 'contents');

        $this->runScenario(function () use ($adapter) {
            $attributes = $adapter->fileSize('path.txt');
            $this->assertInstanceOf(FileAttributes::class, $attributes);
            $this->assertEquals(8, $attributes->fileSize());
        });
    }

    /**
     * @test
     */
    public function setting_visibility(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $this->givenWeHaveAnExistingFile('path.txt', 'contents', [Config::OPTION_VISIBILITY => Visibility::PUBLIC]);

            $this->assertEquals(Visibility::PUBLIC, $adapter->visibility('path.txt')->visibility());

            $adapter->setVisibility('path.txt', Visibility::PRIVATE);

            $this->assertEquals(Visibility::PRIVATE, $adapter->visibility('path.txt')->visibility());

            $adapter->setVisibility('path.txt', Visibility::PUBLIC);

            $this->assertEquals(Visibility::PUBLIC, $adapter->visibility('path.txt')->visibility());
        });
    }

    /**
     * @test
     */
    public function fetching_file_size_of_a_directory(): void
    {
        $this->expectException(UnableToRetrieveMetadata::class);

        $adapter = $this->adapter();

        $this->runScenario(function () use ($adapter) {
            $adapter->createDirectory('path', new Config());
            $adapter->fileSize('path/');
        });
    }

    /**
     * @test
     */
    public function fetching_file_size_of_non_existing_file(): void
    {
        $this->expectException(UnableToRetrieveMetadata::class);

        $this->runScenario(function () {
            $this->adapter()->fileSize('non-existing-file.txt');
        });
    }

    /**
     * @test
     */
    public function fetching_last_modified_of_non_existing_file(): void
    {
        $this->expectException(UnableToRetrieveMetadata::class);

        $this->runScenario(function () {
            $this->adapter()->lastModified('non-existing-file.txt');
        });
    }

    /**
     * @test
     */
    public function fetching_visibility_of_non_existing_file(): void
    {
        $this->expectException(UnableToRetrieveMetadata::class);

        $this->runScenario(function () {
            $this->adapter()->visibility('non-existing-file.txt');
        });
    }

    /**
     * @test
     */
    public function fetching_the_mime_type_of_an_svg_file(): void
    {
        $this->runScenario(function () {
            $this->givenWeHaveAnExistingFile('file.svg', file_get_contents(__DIR__ . '/test_files/flysystem.svg'));

            $mimetype = $this->adapter()->mimeType('file.svg')->mimeType();

            $this->assertStringStartsWith('image/svg', $mimetype);
        });
    }

    /**
     * @test
     */
    public function fetching_mime_type_of_non_existing_file(): void
    {
        $this->expectException(UnableToRetrieveMetadata::class);

        $this->runScenario(function () {
            $this->adapter()->mimeType('non-existing-file.txt');
        });
    }

    /**
     * @test
     */
    public function fetching_unknown_mime_type_of_a_file(): void
    {
        $this->givenWeHaveAnExistingFile(
            'unknown-mime-type.md5',
            file_get_contents(__DIR__ . '/test_files/unknown-mime-type.md5')
        );

        $this->expectException(UnableToRetrieveMetadata::class);

        $this->runScenario(function () {
            $this->adapter()->mimeType('unknown-mime-type.md5');
        });
    }

    /**
     * @test
     */
    public function listing_a_toplevel_directory(): void
    {
        $this->givenWeHaveAnExistingFile('path1.txt');
        $this->givenWeHaveAnExistingFile('path2.txt');

        $this->runScenario(function () {
            $contents = iterator_to_array($this->adapter()->listContents('', true));

            $this->assertCount(2, $contents);
        });
    }

    /**
     * @test
     */
    public function writing_and_reading_with_streams(): void
    {
        $this->runScenario(function () {
            $writeStream = stream_with_contents('contents');
            $adapter = $this->adapter();

            $adapter->writeStream('path.txt', $writeStream, new Config());
            if (is_resource($writeStream)) {
                fclose($writeStream);
            };
            $readStream = $adapter->readStream('path.txt');

            $this->assertIsResource($readStream);
            $contents = stream_get_contents($readStream);
            fclose($readStream);
            $this->assertEquals('contents', $contents);
        });
    }

    /**
     * @test
     */
    public function setting_visibility_on_a_file_that_does_not_exist(): void
    {
        $this->expectException(UnableToSetVisibility::class);

        $this->runScenario(function () {
            $this->adapter()->setVisibility('path.txt', Visibility::PRIVATE);
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
                new Config([Config::OPTION_VISIBILITY => Visibility::PUBLIC])
            );

            $adapter->copy('source.txt', 'destination.txt', new Config());

            $this->assertTrue($adapter->fileExists('source.txt'));
            $this->assertTrue($adapter->fileExists('destination.txt'));
            $this->assertEquals(Visibility::PUBLIC, $adapter->visibility('destination.txt')->visibility());
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
                new Config([Config::OPTION_VISIBILITY => Visibility::PUBLIC])
            );

            $adapter->copy('source.txt', 'destination.txt', new Config());

            $this->assertTrue($adapter->fileExists('source.txt'));
            $this->assertTrue($adapter->fileExists('destination.txt'));
            $this->assertEquals(Visibility::PUBLIC, $adapter->visibility('destination.txt')->visibility());
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
                new Config([Config::OPTION_VISIBILITY => Visibility::PUBLIC])
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
            $this->assertEquals(Visibility::PUBLIC, $adapter->visibility('destination.txt')->visibility());
            $this->assertEquals('contents to be copied', $adapter->read('destination.txt'));
        });
    }

    /**
     * @test
     */
    public function reading_a_file_that_does_not_exist(): void
    {
        $this->expectException(UnableToReadFile::class);

        $this->runScenario(function () {
            $this->adapter()->read('path.txt');
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
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write('path.txt', 'contents', new Config());

            $attributes = $adapter->lastModified('path.txt');

            $this->assertInstanceOf(FileAttributes::class, $attributes);
            $this->assertIsInt($attributes->lastModified());
            $this->assertTrue($attributes->lastModified() > time() - 30);
            $this->assertTrue($attributes->lastModified() < time() + 30);
        });
    }

    /**
     * @test
     */
    public function failing_to_read_a_non_existing_file_into_a_stream(): void
    {
        $this->expectException(UnableToReadFile::class);

        $this->adapter()->readStream('something.txt');
    }

    /**
     * @test
     */
    public function failing_to_read_a_non_existing_file(): void
    {
        $this->expectException(UnableToReadFile::class);

        $this->adapter()->read('something.txt');
    }

    /**
     * @test
     */
    public function creating_a_directory(): void
    {
        $this->runScenario(function () {
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
        });
    }

    /**
     * @test
     */
    public function copying_a_file_with_collision(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write('path.txt', 'new contents', new Config());
            $adapter->write('new-path.txt', 'contents', new Config());

            $adapter->copy('path.txt', 'new-path.txt', new Config());
            $contents = $adapter->read('new-path.txt');

            $this->assertEquals('new contents', $contents);
        });
    }

    protected function assertFileExistsAtPath(string $path): void
    {
        $this->runScenario(function () use ($path) {
            $fileExists = $this->adapter()->fileExists($path);
            $this->assertTrue($fileExists);
        });
    }
}
