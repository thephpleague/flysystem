<?php

declare(strict_types=1);

namespace League\Flysystem\ZipArchive;

use Generator;
use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;

use function iterator_to_array;

/**
 * @group zip
 */
abstract class ZipArchiveAdapterTestCase extends FilesystemAdapterTestCase
{
    private const ARCHIVE = __DIR__ . '/test.zip';

    /**
     * @var StubZipArchiveProvider
     */
    private static $archiveProvider;

    protected function setUp(): void
    {
        static::$adapter = static::createFilesystemAdapter();
        static::removeZipArchive();
        parent::setUp();
    }

    public static function tearDownAfterClass(): void
    {
        static::removeZipArchive();
    }

    protected function tearDown(): void
    {
        static::removeZipArchive();
    }

    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        static::$archiveProvider = new StubZipArchiveProvider(self::ARCHIVE);

        return new ZipArchiveAdapter(self::$archiveProvider, static::getRoot());
    }

    abstract protected static function getRoot(): string;

    /**
     * @test
     */
    public function not_being_able_to_create_the_parent_directory(): void
    {
        $this->expectException(UnableToCreateParentDirectory::class);

        (new ZipArchiveAdapter(new StubZipArchiveProvider('/no-way/this/will/work')))
            ->write('haha', 'lol', new Config());
    }

    /**
     * @test
     */
    public function not_being_able_to_write_a_file_because_the_parent_directory_could_not_be_created(): void
    {
        self::$archiveProvider->stubbedZipArchive()->failNextDirectoryCreation();

        $this->expectException(UnableToWriteFile::class);

        $this->adapter()->write('directoryName/is-here/filename.txt', 'contents', new Config());
    }

    /**
     * @test
     *
     * @dataProvider scenariosThatCauseWritesToFail
     */
    public function scenarios_that_cause_writing_a_file_to_fail(callable $scenario): void
    {
        $this->runScenario($scenario);

        $this->expectException(UnableToWriteFile::class);

        $this->runScenario(function () {
            $handle = stream_with_contents('contents');
            $this->adapter()->writeStream('some/path.txt', $handle, new Config([Config::OPTION_VISIBILITY => Visibility::PUBLIC]));
            is_resource($handle) && @fclose($handle);
        });
    }

    public static function scenariosThatCauseWritesToFail(): Generator
    {
        yield "writing a file fails when writing" => [function () {
            static::$archiveProvider->stubbedZipArchive()->failNextWrite();
        }];

        yield "writing a file fails when setting visibility" => [function () {
            static::$archiveProvider->stubbedZipArchive()->failWhenSettingVisibility();
        }];

        yield "writing a file fails to get the stream contents" => [function () {
            mock_function('stream_get_contents', false);
        }];
    }

    /**
     * @test
     */
    public function failing_to_delete_a_file(): void
    {
        $this->givenWeHaveAnExistingFile('path.txt');
        static::$archiveProvider->stubbedZipArchive()->failNextDeleteName();
        $this->expectException(UnableToDeleteFile::class);

        $this->adapter()->delete('path.txt');
    }

    /**
     * @test
     */
    public function deleting_a_directory(): void
    {
        $this->givenWeHaveAnExistingFile('a.txt');
        $this->givenWeHaveAnExistingFile('one/a.txt');
        $this->givenWeHaveAnExistingFile('one/b.txt');
        $this->givenWeHaveAnExistingFile('two/a.txt');

        $items = iterator_to_array($this->adapter()->listContents('', true));
        $this->assertCount(6, $items);

        $this->adapter()->deleteDirectory('one');

        $items = iterator_to_array($this->adapter()->listContents('', true));
        $this->assertCount(3, $items);
    }

    /**
     * @test
     */
    public function deleting_a_prefixed_directory(): void
    {
        $this->givenWeHaveAnExistingFile('a.txt');
        $this->givenWeHaveAnExistingFile('/one/a.txt');
        $this->givenWeHaveAnExistingFile('one/b.txt');
        $this->givenWeHaveAnExistingFile('two/a.txt');

        $items = iterator_to_array($this->adapter()->listContents('', true));
        $this->assertCount(6, $items);

        $this->adapter()->deleteDirectory('one');

        $items = iterator_to_array($this->adapter()->listContents('', true));
        $this->assertCount(3, $items);
    }

    /**
     * @test
     */
    public function list_root_directory(): void
    {
        $this->givenWeHaveAnExistingFile('a.txt');
        $this->givenWeHaveAnExistingFile('one/a.txt');
        $this->givenWeHaveAnExistingFile('one/b.txt');
        $this->givenWeHaveAnExistingFile('two/a.txt');

        $this->assertCount(6, iterator_to_array($this->adapter()->listContents('', true)));
        $this->assertCount(3, iterator_to_array($this->adapter()->listContents('', false)));
    }

    /**
     * @test
     */
    public function failing_to_create_a_directory(): void
    {
        static::$archiveProvider->stubbedZipArchive()->failNextDirectoryCreation();

        $this->expectException(UnableToCreateDirectory::class);

        $this->adapter()->createDirectory('somewhere', new Config);
    }

    /**
     * @test
     */
    public function failing_to_create_a_directory_because_setting_visibility_fails(): void
    {
        static::$archiveProvider->stubbedZipArchive()->failWhenSettingVisibility();

        $this->expectException(UnableToCreateDirectory::class);

        $this->adapter()->createDirectory('somewhere', new Config([Config::OPTION_DIRECTORY_VISIBILITY => Visibility::PRIVATE]));
    }

    /**
     * @test
     */
    public function failing_to_delete_a_directory(): void
    {
        static::$archiveProvider->stubbedZipArchive()->failWhenDeletingAnIndex();

        $this->givenWeHaveAnExistingFile('here/path.txt');

        $this->expectException(UnableToDeleteDirectory::class);

        $this->adapter()->deleteDirectory('here');
    }

    /**
     * @test
     */
    public function setting_visibility_on_a_directory(): void
    {
        $adapter = $this->adapter();
        $adapter->createDirectory('pri-dir', new Config([Config::OPTION_DIRECTORY_VISIBILITY => Visibility::PRIVATE]));
        $adapter->createDirectory('pub-dir', new Config([Config::OPTION_DIRECTORY_VISIBILITY => Visibility::PUBLIC]));

        $this->expectNotToPerformAssertions();
    }

    /**
     * @test
     */
    public function failing_to_move_a_file(): void
    {
        $this->givenWeHaveAnExistingFile('somewhere/here.txt');

        static::$archiveProvider->stubbedZipArchive()->failNextDirectoryCreation();

        $this->expectException(UnableToMoveFile::class);

        $this->adapter()->move('somewhere/here.txt', 'to-here/path.txt', new Config);
    }

    /**
     * @test
     */
    public function failing_to_copy_a_file(): void
    {
        $this->givenWeHaveAnExistingFile('here.txt');

        static::$archiveProvider->stubbedZipArchive()->failNextWrite();

        $this->expectException(UnableToCopyFile::class);

        $this->adapter()->copy('here.txt', 'here.txt', new Config);
    }

    /**
     * @test
     */
    public function failing_to_set_visibility_because_the_file_does_not_exist(): void
    {
        $this->expectException(UnableToSetVisibility::class);

        $this->adapter()->setVisibility('path.txt', Visibility::PUBLIC);
    }

    /**
     * @test
     */
    public function deleting_a_directory_with_files_in_it(): void
    {
        $this->givenWeHaveAnExistingFile('nested/path-a.txt');
        $this->givenWeHaveAnExistingFile('nested/path-b.txt');

        $this->adapter()->deleteDirectory('nested');
        $listing = iterator_to_array($this->adapter()->listContents('', true));

        self::assertEquals([], $listing);
    }

    /**
     * @test
     */
    public function failing_to_set_visibility_because_setting_it_fails(): void
    {
        $this->givenWeHaveAnExistingFile('path.txt');
        static::$archiveProvider->stubbedZipArchive()->failWhenSettingVisibility();

        $this->expectException(UnableToSetVisibility::class);

        $this->adapter()->setVisibility('path.txt', Visibility::PUBLIC);
    }

    /**
     * @test
     *
     * @fixme Move to FilesystemAdapterTestCase once all adapters pass
     */
    public function moving_a_file_and_overwriting(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write(
                'source.txt',
                'contents to be moved',
                new Config([Config::OPTION_VISIBILITY => Visibility::PUBLIC])
            );
            $adapter->write(
                'destination.txt',
                'contents to be overwritten',
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
            $this->assertEquals('contents to be moved', $adapter->read('destination.txt'));
        });
    }

    protected static function removeZipArchive(): void
    {
        if ( ! file_exists(self::ARCHIVE)) {
            return;
        }

        unlink(self::ARCHIVE);
    }
}
