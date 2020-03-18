<?php

declare(strict_types=1);

namespace League\Flysystem\Local;

use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\SymbolicLinkEncountered;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;

use function file_get_contents;
use function file_put_contents;
use function fileperms;
use function iterator_to_array;
use function mkdir;
use function symlink;

use const LOCK_EX;

/**
 * @group local
 */
class LocalFilesystemAdapterTest extends FilesystemAdapterTestCase
{
    private const ROOT = __DIR__ . '/test-root';

    protected function setUp(): void
    {
        reset_function_mocks();
        delete_directory(static::ROOT);
    }

    protected function tearDown(): void
    {
        reset_function_mocks();
        delete_directory(static::ROOT);
    }

    /**
     * @test
     */
    public function creating_a_local_filesystem_creates_a_root_directory(): void
    {
        new LocalFilesystemAdapter(static::ROOT);
        $this->assertDirectoryExists(static::ROOT);
    }

    /**
     * @test
     */
    public function not_being_able_to_create_a_root_directory_results_in_an_exception(): void
    {
        $this->expectException(UnableToCreateDirectory::class);
        new LocalFilesystemAdapter('/cannot-create/this-directory/');
    }

    /**
     * @test
     */
    public function writing_a_file(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);

        $adapter->write('/file.txt', 'contents', new Config());

        $this->assertFileExists(static::ROOT . '/file.txt');
        $contents = file_get_contents(static::ROOT . '/file.txt');
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function writing_a_file_with_a_stream(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $stream = stream_with_contents('contents');

        $adapter->writeStream('/file.txt', $stream, new Config());
        fclose($stream);

        $this->assertFileExists(static::ROOT . '/file.txt');
        $contents = file_get_contents(static::ROOT . '/file.txt');
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function writing_a_file_with_a_stream_and_visibility(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $stream = stream_with_contents('something');

        $adapter->writeStream('/file.txt', $stream, new Config(['visibility' => Visibility::PRIVATE]));
        fclose($stream);

        $this->assertFileContains(static::ROOT . '/file.txt', 'something');
        $this->assertFileHasPermissions(static::ROOT . '/file.txt', 0600);
    }

    /**
     * @test
     */
    public function writing_a_file_with_visibility(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT, new PortableVisibilityConverter());
        $adapter->write('/file.txt', 'contents', new Config(['visibility' => 'private']));
        $this->assertFileContains(static::ROOT . '/file.txt', 'contents');
        $this->assertFileHasPermissions(static::ROOT . '/file.txt', 0600);
    }

    /**
     * @test
     */
    public function failing_to_set_visibility(): void
    {
        $this->expectException(UnableToSetVisibility::class);
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->setVisibility('/file.txt', Visibility::PUBLIC);
    }

    /**
     * @test
     */
    public function failing_to_write_a_file(): void
    {
        $this->expectException(UnableToWriteFile::class);
        (new LocalFilesystemAdapter('/'))->write('/cannot-create-a-file-here', 'contents', new Config());
    }

    /**
     * @test
     */
    public function failing_to_write_a_file_using_a_stream(): void
    {
        $this->expectException(UnableToWriteFile::class);
        try {
            $stream = stream_with_contents('something');
            (new LocalFilesystemAdapter('/'))->writeStream('/cannot-create-a-file-here', $stream, new Config());
        } finally {
            fclose($stream);
        }
    }

    /**
     * @test
     */
    public function deleting_a_file(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        file_put_contents(static::ROOT . '/file.txt', 'contents');
        $adapter->delete('/file.txt');
        $this->assertFileNotExists(static::ROOT . '/file.txt');
    }

    /**
     * @test
     */
    public function deleting_a_file_that_does_not_exist(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->delete('/file.txt');
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function deleting_a_file_that_cannot_be_deleted(): void
    {
        $this->givenWeHaveAnExistingFile('here.txt');
        mock_function('unlink', false);

        $this->expectException(UnableToDeleteFile::class);

        $this->adapter()->delete('here.txt');
    }

    /**
     * @test
     */
    public function checking_if_a_file_exists(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        file_put_contents(static::ROOT . '/file.txt', 'contents');

        $this->assertTrue($adapter->fileExists('/file.txt'));
    }

    /**
     * @test
     */
    public function checking_if_a_file_exists_that_does_not_exsist(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);

        $this->assertFalse($adapter->fileExists('/file.txt'));
    }

    /**
     * @test
     */
    public function listing_contents(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->write('directory/filename.txt', 'content', new Config());
        $adapter->write('filename.txt', 'content', new Config());
        $contents = iterator_to_array($adapter->listContents('/', false));

        $this->assertCount(2, $contents);
        $this->assertContainsOnlyInstancesOf(StorageAttributes::class, $contents);
    }

    /**
     * @test
     */
    public function listing_contents_recursively(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->write('directory/filename.txt', 'content', new Config());
        $adapter->write('filename.txt', 'content', new Config());
        $contents = iterator_to_array($adapter->listContents('/', true));

        $this->assertCount(3, $contents);
        $this->assertContainsOnlyInstancesOf(StorageAttributes::class, $contents);
    }

    /**
     * @test
     */
    public function listing_a_non_existing_directory(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $contents = iterator_to_array($adapter->listContents('/directory/', false));

        $this->assertCount(0, $contents);
    }

    /**
     * @test
     */
    public function listing_directory_contents_with_link_skipping(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT, null, LOCK_EX, LocalFilesystemAdapter::SKIP_LINKS);
        file_put_contents(static::ROOT . '/file.txt', 'content');
        symlink(static::ROOT . '/file.txt', static::ROOT . '/link.txt');

        $contents = iterator_to_array($adapter->listContents('/', true));

        $this->assertCount(1, $contents);
    }

    /**
     * @test
     */
    public function listing_directory_contents_with_disallowing_links(): void
    {
        $this->expectException(SymbolicLinkEncountered::class);
        $adapter = new LocalFilesystemAdapter(static::ROOT, null, LOCK_EX, LocalFilesystemAdapter::DISALLOW_LINKS);
        file_put_contents(static::ROOT . '/file.txt', 'content');
        symlink(static::ROOT . '/file.txt', static::ROOT . '/link.txt');

        iterator_to_array($adapter->listContents('/', true));
    }

    /**
     * @test
     */
    public function deleting_a_directory(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        mkdir(static::ROOT . '/directory/subdir/', 0744, true);
        $this->assertDirectoryExists(static::ROOT . '/directory/subdir/');
        file_put_contents(static::ROOT . '/directory/subdir/file.txt', 'content');
        symlink(static::ROOT . '/directory/subdir/file.txt', static::ROOT . '/directory/subdir/link.txt');
        $adapter->deleteDirectory('directory/subdir');
        $this->assertDirectoryNotExists(static::ROOT . '/directory/subdir/');
        $adapter->deleteDirectory('directory');
        $this->assertDirectoryNotExists(static::ROOT . '/directory/');
    }

    /**
     * @test
     */
    public function deleting_directories_with_other_directories_in_it(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->write('a/b/c/d/e.txt', 'contents', new Config());
        $adapter->deleteDirectory('a/b');
        $this->assertDirectoryExists(static::ROOT . '/a');
        $this->assertDirectoryNotExists(static::ROOT . '/a/b');
    }

    /**
     * @test
     */
    public function deleting_a_non_existing_directory(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->deleteDirectory('/non-existing-directory/');
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function not_being_able_to_delete_a_directory(): void
    {
        $this->expectException(UnableToDeleteDirectory::class);

        mock_function('rmdir', false);

        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->createDirectory('/etc/', new Config());
        $adapter->deleteDirectory('/etc/');
    }

    /**
     * @test
     */
    public function not_being_able_to_delete_a_sub_directory(): void
    {
        $this->expectException(UnableToDeleteDirectory::class);

        mock_function('rmdir', false);

        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->createDirectory('/etc/subdirectory/', new Config());
        $adapter->deleteDirectory('/etc/');
    }

    /**
     * @test
     */
    public function creating_a_directory(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->createDirectory('public', new Config(['visibility' => 'public']));
        $this->assertDirectoryExists(static::ROOT . '/public');
        $this->assertFileHasPermissions(static::ROOT . '/public', 0755);

        $adapter->createDirectory('private', new Config(['visibility' => 'private']));
        $this->assertDirectoryExists(static::ROOT . '/private');
        $this->assertFileHasPermissions(static::ROOT . '/private', 0700);

        $adapter->createDirectory('also_private', new Config(['directory_visibility' => 'private']));
        $this->assertDirectoryExists(static::ROOT . '/also_private');
        $this->assertFileHasPermissions(static::ROOT . '/also_private', 0700);
    }

    /**
     * @test
     */
    public function not_being_able_to_create_a_directory(): void
    {
        $this->expectException(UnableToCreateDirectory::class);
        $adapter = new LocalFilesystemAdapter('/');
        $adapter->createDirectory('/something/', new Config());
    }

    /**
     * @test
     */
    public function creating_a_directory_is_idempotent(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->createDirectory('/something/', new Config(['visibility' => 'private']));
        $this->assertFileHasPermissions(static::ROOT . '/something', 0700);
        $adapter->createDirectory('/something/', new Config(['visibility' => 'public']));
        $this->assertFileHasPermissions(static::ROOT . '/something', 0755);
    }

    /**
     * @test
     */
    public function retrieving_visibility(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->write('public.txt', 'contents', new Config(['visibility' => 'public']));
        $this->assertEquals('public', $adapter->visibility('public.txt')->visibility());
        $adapter->write('private.txt', 'contents', new Config(['visibility' => 'private']));
        $this->assertEquals('private', $adapter->visibility('private.txt')->visibility());
    }

    /**
     * @test
     */
    public function not_being_able_to_retrieve_visibility(): void
    {
        $this->expectException(UnableToRetrieveMetadata::class);
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->visibility('something.txt');
    }

    /**
     * @test
     */
    public function moving_a_file(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->write('first.txt', 'contents', new Config());
        $this->assertFileExists(static::ROOT . '/first.txt');
        $adapter->move('first.txt', 'second.txt', new Config());
        $this->assertFileExists(static::ROOT . '/second.txt');
        $this->assertFileNotExists(static::ROOT . '/first.txt');
    }

    /**
     * @test
     */
    public function not_being_able_to_move_a_file(): void
    {
        $this->expectException(UnableToMoveFile::class);
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->move('first.txt', 'second.txt', new Config());
    }

    /**
     * @test
     */
    public function copying_a_file(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->write('first.txt', 'contents', new Config());
        $adapter->copy('first.txt', 'second.txt', new Config());
        $this->assertFileExists(static::ROOT . '/second.txt');
        $this->assertFileExists(static::ROOT . '/first.txt');
    }

    /**
     * @test
     */
    public function not_being_able_to_copy_a_file(): void
    {
        $this->expectException(UnableToCopyFile::class);
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->copy('first.txt', 'second.txt', new Config());
    }

    /**
     * @test
     */
    public function getting_mimetype(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->write(
            'flysystem.svg',
            (string) file_get_contents(__DIR__ . '/../AdapterTestUtilities/test_files/flysystem.svg'),
            new Config()
        );
        $this->assertEquals('image/svg', $adapter->mimeType('flysystem.svg')->mimeType());
    }

    /**
     * @test
     */
    public function not_being_able_to_get_mimetype(): void
    {
        $this->expectException(UnableToRetrieveMetadata::class);
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->mimeType('flysystem.svg');
    }

    /**
     * @test
     */
    public function getting_last_modified(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->write('first.txt', 'contents', new Config());
        mock_function('filemtime', $now = time());
        $lastModified = $adapter->lastModified('first.txt')->lastModified();
        $this->assertEquals($now, $lastModified);
    }

    /**
     * @test
     */
    public function not_being_able_to_get_last_modified(): void
    {
        $this->expectException(UnableToRetrieveMetadata::class);
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->lastModified('first.txt');
    }

    /**
     * @test
     */
    public function getting_file_size(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->write('first.txt', 'contents', new Config());
        $fileSize = $adapter->fileSize('first.txt');
        $this->assertEquals(8, $fileSize->fileSize());
    }

    /**
     * @test
     */
    public function not_being_able_to_get_file_size(): void
    {
        $this->expectException(UnableToRetrieveMetadata::class);
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->fileSize('first.txt');
    }

    /**
     * @test
     */
    public function reading_a_file(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->write('path.txt', 'contents', new Config());
        $contents = $adapter->read('path.txt');
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function not_being_able_to_read_a_file(): void
    {
        $this->expectException(UnableToReadFile::class);
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->read('path.txt');
    }

    /**
     * @test
     */
    public function reading_a_stream(): void
    {
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->write('path.txt', 'contents', new Config());
        $contents = $adapter->readStream('path.txt');
        $this->assertIsResource($contents);
        $fileContents = stream_get_contents($contents);
        fclose($contents);
        $this->assertEquals('contents', $fileContents);
    }

    /**
     * @test
     */
    public function not_being_able_to_stream_read_a_file(): void
    {
        $this->expectException(UnableToReadFile::class);
        $adapter = new LocalFilesystemAdapter(static::ROOT);
        $adapter->readStream('path.txt');
    }

    /* //////////////////////
    // These are the utils //
    ////////////////////// */

    /**
     * @param string $file
     * @param int    $expectedPermissions
     */
    private function assertFileHasPermissions(string $file, int $expectedPermissions): void
    {
        clearstatcache(false, $file);
        $permissions = fileperms($file) & 0777;
        $this->assertEquals($expectedPermissions, $permissions);
    }

    /**
     * @param string $file
     * @param string $expectedContents
     */
    private function assertFileContains(string $file, string $expectedContents): void
    {
        $this->assertFileExists($file);
        $contents = file_get_contents($file);
        $this->assertEquals($expectedContents, $contents);
    }

    protected function createFilesystemAdapter(): FilesystemAdapter
    {
        return new LocalFilesystemAdapter(static::ROOT);
    }
}
