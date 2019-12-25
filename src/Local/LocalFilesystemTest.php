<?php

declare(strict_types=1);

namespace League\Flysystem\Local;

use League\Flysystem\Config;
use League\Flysystem\StorageAttributes;
use League\Flysystem\SymbolicLinkEncountered;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToUpdateFile;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function file_put_contents;
use function fileperms;
use function fwrite;
use function getenv;
use function is_dir;
use function iterator_to_array;
use function mkdir;
use function rewind;
use function symlink;

use const LOCK_EX;

class LocalFilesystemTest extends TestCase
{
    private const ROOT = __DIR__ . '/test-root';

    protected function setUp(): void
    {
        reset_function_mocks();
        static::deleteDirectory(static::ROOT);
    }

    protected function tearDown(): void
    {
        reset_function_mocks();
        static::deleteDirectory(static::ROOT);
    }

    private static function deleteDirectory(string $dir)
    {
        if ( ! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }
            if (is_dir("$dir/$file")) {
                static::deleteDirectory("$dir/$file");
            } else {
                unlink("$dir/$file");
            }
        }
        rmdir($dir);
    }

    /**
     * @test
     */
    public function creating_a_local_filesystem_creates_a_root_directory()
    {
        new LocalFilesystem(static::ROOT);
        $this->assertDirectoryExists(static::ROOT);
    }

    /**
     * @test
     */
    public function not_being_able_to_create_a_root_directory_results_in_an_exception()
    {
        $this->expectException(UnableToCreateDirectory::class);
        new LocalFilesystem('/cannot-create/this-directory/');
    }

    /**
     * @test
     */
    public function writing_a_file()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->write('/file.txt', 'contents', new Config());
        $this->assertFileExists(static::ROOT . '/file.txt');
        $contents = file_get_contents(static::ROOT . '/file.txt');
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function updating_a_file()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->write('/file.txt', 'contents', new Config());
        $adapter->update('/file.txt', 'new contents', new Config());
        $this->assertFileExists(static::ROOT . '/file.txt');
        $contents = file_get_contents(static::ROOT . '/file.txt');
        $this->assertEquals('new contents', $contents);
    }

    /**
     * @test
     */
    public function writing_a_file_with_a_stream()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        $stream = $this->streamWithContents('contents');
        $adapter->writeStream('/file.txt', $stream, new Config());
        fclose($stream);

        $this->assertFileExists(static::ROOT . '/file.txt');
        $contents = file_get_contents(static::ROOT . '/file.txt');
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function updating_a_file_with_a_stream()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->write('/file.txt', 'contents', new Config());
        $stream = $this->streamWithContents('new contents');
        $adapter->updateStream('/file.txt', $stream, new Config());
        fclose($stream);

        $this->assertFileExists(static::ROOT . '/file.txt');
        $contents = file_get_contents(static::ROOT . '/file.txt');
        $this->assertEquals('new contents', $contents);
    }

    /**
     * @test
     */
    public function writing_a_file_with_a_stream_and_visibility()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        $stream = $this->streamWithContents('something');
        $adapter->writeStream('/file.txt', $stream, new Config(['visibility' => Visibility::PRIVATE]));
        fclose($stream);

        $this->assertFileContains(static::ROOT . '/file.txt', 'something');
        $this->assertFileHasPermissions(static::ROOT . '/file.txt', 0600);
    }

    /**
     * @test
     */
    public function writing_a_file_with_visibility()
    {
        $adapter = new LocalFilesystem(
            static::ROOT, new PublicAndPrivateVisibilityInterpreting()
        );
        $adapter->write('/file.txt', 'contents', new Config(['visibility' => 'private']));
        $this->assertFileContains(static::ROOT . '/file.txt', 'contents');
        $this->assertFileHasPermissions(static::ROOT . '/file.txt', 0600);
    }

    /**
     * @test
     */
    public function setting_visibility()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->write('/file.txt', 'contents', new Config());
        $adapter->setVisibility('/file.txt', Visibility::PUBLIC);
        $this->assertFileHasPermissions(static::ROOT . '/file.txt', 0644);
        $adapter->setVisibility('/file.txt', Visibility::PRIVATE);
        $this->assertFileHasPermissions(static::ROOT . '/file.txt', 0600);
    }

    /**
     * @test
     */
    public function failing_to_set_visibility()
    {
        $this->expectException(UnableToSetVisibility::class);
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->setVisibility('/file.txt', Visibility::PUBLIC);
    }

    /**
     * @test
     */
    public function failing_to_write_a_file()
    {
        $this->expectException(UnableToWriteFile::class);
        (new LocalFilesystem('/'))->write('/cannot-create-a-file-here', 'contents', new Config());
    }

    /**
     * @test
     */
    public function failing_to_update_a_file()
    {
        $this->expectException(UnableToUpdateFile::class);
        (new LocalFilesystem('/'))->update('/cannot-create-a-file-here', 'contents', new Config());
    }

    /**
     * @test
     */
    public function failing_to_write_a_file_using_a_stream()
    {
        $this->expectException(UnableToWriteFile::class);
        try {
            $stream = $this->streamWithContents('something');
            (new LocalFilesystem('/'))->writeStream('/cannot-create-a-file-here', 'contents', new Config());
        } finally {
            fclose($stream);
        }
    }

    /**
     * @test
     */
    public function failing_to_update_a_file_using_a_stream()
    {
        $this->expectException(UnableToUpdateFile::class);
        try {
            $stream = $this->streamWithContents('something');
            (new LocalFilesystem('/'))->updateStream('/cannot-create-a-file-here', 'contents', new Config());
        } finally {
            fclose($stream);
        }
    }

    /**
     * @test
     */
    public function deleting_a_file()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        file_put_contents(static::ROOT . '/file.txt', 'contents');
        $adapter->delete('/file.txt');
        $this->assertFileNotExists(static::ROOT . '/file.txt');
    }

    /**
     * @test
     */
    public function deleting_a_file_that_does_not_exist()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->delete('/file.txt');
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function deleting_a_file_that_cannot_be_deleted()
    {
        $this->maybeSkipDangerousTests();
        $this->expectException(UnableToDeleteFile::class);
        $adapter = new LocalFilesystem('/');
        $adapter->delete('/etc/hosts');
    }

    /**
     * @test
     */
    public function checking_if_a_file_exists()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        file_put_contents(static::ROOT . '/file.txt', 'contents');

        $this->assertTrue($adapter->fileExists('/file.txt'));
    }

    /**
     * @test
     */
    public function checking_if_a_file_exists_that_does_not_exsist()
    {
        $adapter = new LocalFilesystem(static::ROOT);

        $this->assertFalse($adapter->fileExists('/file.txt'));
    }

    /**
     * @test
     */
    public function listing_contents()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->write('directory/filename.txt', 'content', new Config());
        $adapter->write('filename.txt', 'content', new Config());
        $contents = iterator_to_array($adapter->listContents('/', false));

        $this->assertCount(2, $contents);
        $this->assertContainsOnlyInstancesOf(StorageAttributes::class, $contents);
    }

    /**
     * @test
     */
    public function listing_contents_recursively()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->write('directory/filename.txt', 'content', new Config());
        $adapter->write('filename.txt', 'content', new Config());
        $contents = iterator_to_array($adapter->listContents('/', true));

        $this->assertCount(3, $contents);
        $this->assertContainsOnlyInstancesOf(StorageAttributes::class, $contents);
    }

    /**
     * @test
     */
    public function listing_a_non_existing_directory()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        $contents = iterator_to_array($adapter->listContents('/directory/', false));

        $this->assertCount(0, $contents);
    }

    /**
     * @test
     */
    public function listing_directory_contents_with_link_skipping()
    {
        $adapter = new LocalFilesystem(static::ROOT, null, LOCK_EX, LocalFilesystem::SKIP_LINKS);
        file_put_contents(static::ROOT . '/file.txt', 'content');
        symlink(static::ROOT . '/file.txt', static::ROOT . '/link.txt');

        $contents = iterator_to_array($adapter->listContents('/', true));

        $this->assertCount(1, $contents);
    }

    /**
     * @test
     */
    public function listing_directory_contents_with_disallowing_links()
    {
        $this->expectException(SymbolicLinkEncountered::class);
        $adapter = new LocalFilesystem(static::ROOT, null, LOCK_EX, LocalFilesystem::DISALLOW_LINKS);
        file_put_contents(static::ROOT . '/file.txt', 'content');
        symlink(static::ROOT . '/file.txt', static::ROOT . '/link.txt');

        $adapter->listContents('/', true)->next();
    }

    /**
     * @test
     */
    public function deleting_a_directory()
    {
        $adapter = new LocalFilesystem(static::ROOT);
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
    public function deleting_a_non_existing_directory()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->deleteDirectory('/non-existing-directory/');
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function not_being_able_to_delete_a_directory()
    {
        $this->expectException(UnableToDeleteDirectory::class);

        mock_function('rmdir', false);

        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->createDirectory('/etc/', new Config());
        $adapter->deleteDirectory('/etc/');
    }

    /**
     * @test
     */
    public function not_being_able_to_delete_a_sub_directory()
    {
        $this->expectException(UnableToDeleteDirectory::class);

        mock_function('rmdir', false);

        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->createDirectory('/etc/subdirectory/', new Config());
        $adapter->deleteDirectory('/etc/');
    }

    /**
     * @test
     */
    public function creating_a_directory()
    {
        $adapter = new LocalFilesystem(static::ROOT);
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
    public function not_being_able_to_create_a_directory()
    {
        $this->expectException(UnableToCreateDirectory::class);
        $adapter = new LocalFilesystem('/');
        $adapter->createDirectory('/something/', new Config());
    }

    /**
     * @test
     */
    public function creating_a_directory_is_idempotent()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->createDirectory('/something/', new Config(['visibility' => 'private']));
        $this->assertFileHasPermissions(static::ROOT . '/something', 0700);
        $adapter->createDirectory('/something/', new Config(['visibility' => 'public']));
        $this->assertFileHasPermissions(static::ROOT . '/something', 0755);
    }

    /**
     * @test
     */
    public function retrieving_visibility()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->write('public.txt', 'contents', new Config(['visibility' => 'public']));
        $this->assertEquals('public', $adapter->visibility('public.txt'));
        $adapter->write('private.txt', 'contents', new Config(['visibility' => 'private']));
        $this->assertEquals('private', $adapter->visibility('private.txt'));
    }

    /**
     * @test
     */
    public function not_being_able_to_retrieve_visibility()
    {
        $this->expectException(UnableToRetrieveMetadata::class);
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->visibility('something.txt');
    }

    /**
     * @test
     */
    public function moving_a_file()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->write('first.txt', 'contents', new Config());
        $this->assertFileExists(static::ROOT . '/first.txt');
        $adapter->move('first.txt', 'second.txt', new Config());
        $this->assertFileExists(static::ROOT . '/second.txt');
        $this->assertFileNotExists(static::ROOT . '/first.txt');
    }

    /**
     * @test
     */
    public function not_being_able_to_move_a_file()
    {
        $this->expectException(UnableToMoveFile::class);
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->move('first.txt', 'second.txt', new Config());
    }

    /**
     * @test
     */
    public function copying_a_file()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->write('first.txt', 'contents', new Config());
        $adapter->copy('first.txt', 'second.txt', new Config());
        $this->assertFileExists(static::ROOT . '/second.txt');
        $this->assertFileExists(static::ROOT . '/first.txt');
    }

    /**
     * @test
     */
    public function not_being_able_to_copy_a_file()
    {
        $this->expectException(UnableToCopyFile::class);
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->copy('first.txt', 'second.txt', new Config());
    }

    /**
     * @test
     */
    public function getting_mimetype()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->write(
            'flysystem.svg',
            file_get_contents(__DIR__ . '/../../test_files/flysystem.svg'),
            new Config()
        );
        $this->assertEquals('image/svg', $adapter->mimeType('flysystem.svg'));
    }

    /**
     * @test
     */
    public function getting_last_modified()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->write('first.txt', 'contents', new Config());
        mock_function('filemtime', $now = time());
        $lastModified = $adapter->lastModified('first.txt');
        $this->assertEquals($now, $lastModified);
    }

    /**
     * @test
     */
    public function not_being_able_to_get_last_modified()
    {
        $this->expectException(UnableToRetrieveMetadata::class);
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->lastModified('first.txt');
    }

    /**
     * @test
     */
    public function getting_file_size()
    {
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->write('first.txt', 'contents', new Config());
        $fileSize = $adapter->fileSize('first.txt');
        $this->assertEquals(8, $fileSize);
    }

    /**
     * @test
     */
    public function not_being_able_to_get_file_size()
    {
        $this->expectException(UnableToRetrieveMetadata::class);
        $adapter = new LocalFilesystem(static::ROOT);
        $adapter->fileSize('first.txt');
    }

    /* //////////////////////
    // These are the utils //
    ////////////////////// */

    private function streamWithContents(string $contents)
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $contents);
        rewind($stream);

        return $stream;
    }

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

    private function maybeSkipDangerousTests(): void
    {
        if (posix_getuid() === 0 || getenv('FLYSYSTEM_TEST_DANGEROUS_THINGS') !== 'yes') {
            $this->markTestSkipped(
                'Skipping this out of precaution. Use FLYSYSTEM_TEST_DANGEROUS_THINGS=yes to test them'
            );
        }
    }
}
