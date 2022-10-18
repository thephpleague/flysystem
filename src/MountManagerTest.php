<?php

namespace League\Flysystem;

use League\Flysystem\AdapterTestUtilities\ExceptionThrowingFilesystemAdapter;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;

use function fclose;
use function is_resource;
use function stream_get_contents;
use function tmpfile;

/**
 * @group core
 */
class MountManagerTest extends TestCase
{
    /**
     * @var ExceptionThrowingFilesystemAdapter
     */
    private $firstStubAdapter;

    /**
     * @var ExceptionThrowingFilesystemAdapter
     */
    private $secondStubAdapter;

    /**
     * @var MountManager
     */
    private $mountManager;

    /**
     * @var Filesystem
     */
    private $firstFilesystem;

    /**
     * @var Filesystem
     */
    private $secondFilesystem;

    protected function setUp(): void
    {
        $firstFilesystemAdapter = new InMemoryFilesystemAdapter();
        $secondFilesystemAdapter = new InMemoryFilesystemAdapter();
        $this->firstStubAdapter = new ExceptionThrowingFilesystemAdapter($firstFilesystemAdapter);
        $this->secondStubAdapter = new ExceptionThrowingFilesystemAdapter($secondFilesystemAdapter);

        $this->mountManager = new MountManager([
            'first' => $this->firstFilesystem = new Filesystem($this->firstStubAdapter),
            'second' => $this->secondFilesystem = new Filesystem($this->secondStubAdapter),
       ]);
    }

    /**
     * @test
     */
    public function writing_a_file(): void
    {
        $this->mountManager->write('first://file.txt', 'content');
        $this->mountManager->write('second://another-file.txt', 'content');

        $this->assertTrue($this->firstFilesystem->fileExists('file.txt'));
        $this->assertFalse($this->secondFilesystem->fileExists('file.txt'));

        $this->assertFalse($this->firstFilesystem->fileExists('another-file.txt'));
        $this->assertTrue($this->secondFilesystem->fileExists('another-file.txt'));
    }

    /**
     * @test
     */
    public function writing_a_file_with_a_stream(): void
    {
        $stream = stream_with_contents('contents');
        $this->mountManager->writeStream('first://location.txt', $stream);

        $this->assertTrue($this->firstFilesystem->fileExists('location.txt'));
        $this->assertEquals('contents', $this->firstFilesystem->read('location.txt'));
    }

    /**
     * @test
     */
    public function not_being_able_to_write_a_file(): void
    {
        $this->firstStubAdapter->stageException('write', 'file.txt', UnableToWriteFile::atLocation('file.txt'));

        $this->expectException(UnableToWriteFile::class);

        $this->mountManager->write('first://file.txt', 'content');
    }

    /**
     * @test
     */
    public function not_being_able_to_stream_write_a_file(): void
    {
        $handle = tmpfile();
        $this->firstStubAdapter->stageException('writeStream', 'file.txt', UnableToWriteFile::atLocation('file.txt'));

        $this->expectException(UnableToWriteFile::class);

        try {
            $this->mountManager->writeStream('first://file.txt', $handle);
        } finally {
            is_resource($handle) && fclose($handle);
        }
    }

    /**
     * @description This test method is so ugly, but I don't have the energy to create a nice test for every single one of these method.
     * @test
     * @dataProvider dpMetadataRetrieverMethods
     */
    public function failing_a_one_param_method(string $method, FilesystemOperationFailed $exception): void
    {
        $this->firstStubAdapter->stageException($method, 'location.txt', $exception);

        $this->expectException(get_class($exception));

        $this->mountManager->{$method}('first://location.txt');
    }

    public function dpMetadataRetrieverMethods(): iterable
    {
        yield 'mimeType' => ['mimeType', UnableToRetrieveMetadata::mimeType('location.txt')];
        yield 'fileSize' => ['fileSize', UnableToRetrieveMetadata::fileSize('location.txt')];
        yield 'lastModified' => ['lastModified', UnableToRetrieveMetadata::lastModified('location.txt')];
        yield 'visibility' => ['visibility', UnableToRetrieveMetadata::visibility('location.txt')];
        yield 'delete' => ['delete', UnableToDeleteFile::atLocation('location.txt')];
        yield 'deleteDirectory' => ['deleteDirectory', UnableToDeleteDirectory::atLocation('location.txt')];
        yield 'createDirectory' => ['createDirectory', UnableToCreateDirectory::atLocation('location.txt')];
        yield 'read' => ['read', UnableToReadFile::fromLocation('location.txt')];
        yield 'readStream' => ['readStream', UnableToReadFile::fromLocation('location.txt')];
        yield 'fileExists' => ['fileExists', UnableToCheckFileExistence::forLocation('location.txt')];
    }

    /**
     * @test
     */
    public function reading_a_file(): void
    {
        $this->secondFilesystem->write('location.txt', 'contents');

        $contents = $this->mountManager->read('second://location.txt');

        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function reading_a_file_as_a_stream(): void
    {
        $this->secondFilesystem->write('location.txt', 'contents');

        $handle = $this->mountManager->readStream('second://location.txt');
        $contents = stream_get_contents($handle);
        fclose($handle);

        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function checking_existence_for_an_existing_file(): void
    {
        $this->secondFilesystem->write('location.txt', 'contents');

        $existence = $this->mountManager->fileExists('second://location.txt');

        $this->assertTrue($existence);
    }

    /**
     * @test
     */
    public function checking_existence_for_an_non_existing_file(): void
    {
        $existence = $this->mountManager->fileExists('second://location.txt');

        $this->assertFalse($existence);
    }

    /**
     * @test
     */
    public function checking_existence_for_an_non_existing_directory(): void
    {
        $existence = $this->mountManager->directoryExists('second://some-directory');

        $this->assertFalse($existence);
    }

    /**
     * @test
     */
    public function checking_existence_for_an_existing_directory(): void
    {
        $this->secondFilesystem->write('nested/location.txt', 'contents');

        $existence = $this->mountManager->directoryExists('second://nested');

        $this->assertTrue($existence);
    }

    /**
     * @test
     */
    public function checking_existence_for_an_existing_file_using_has(): void
    {
        $this->secondFilesystem->write('location.txt', 'contents');

        $existence = $this->mountManager->has('second://location.txt');

        $this->assertTrue($existence);
    }

    /**
     * @test
     */
    public function checking_existence_for_an_non_existing_file_using_has(): void
    {
        $existence = $this->mountManager->has('second://location.txt');

        $this->assertFalse($existence);
    }

    /**
     * @test
     */
    public function checking_existence_for_an_non_existing_directory_using_has(): void
    {
        $existence = $this->mountManager->has('second://some-directory');

        $this->assertFalse($existence);
    }

    /**
     * @test
     */
    public function checking_existence_for_an_existing_directory_using_has(): void
    {
        $this->secondFilesystem->write('nested/location.txt', 'contents');

        $existence = $this->mountManager->has('second://nested');

        $this->assertTrue($existence);
    }

    /**
     * @test
     */
    public function deleting_a_file(): void
    {
        $this->firstFilesystem->write('location.txt', 'contents');

        $this->mountManager->delete('first://location.txt');

        $this->assertFalse($this->firstFilesystem->fileExists('location.txt'));
    }

    /**
     * @test
     */
    public function deleting_a_directory(): void
    {
        $this->firstFilesystem->write('dirname/location.txt', 'contents');

        $this->mountManager->deleteDirectory('first://dirname');

        $this->assertFalse($this->firstFilesystem->fileExists('dirname/location.txt'));
    }

    /**
     * @test
     */
    public function setting_visibility(): void
    {
        $this->firstFilesystem->write('location.txt', 'contents');
        $this->firstFilesystem->setVisibility('location.txt', Visibility::PRIVATE);

        $this->mountManager->setVisibility('first://location.txt', Visibility::PUBLIC);

        $this->assertEquals(Visibility::PUBLIC, $this->firstFilesystem->visibility('location.txt'));
    }

    /**
     * @test
     */
    public function retrieving_metadata(): void
    {
        $now = time();
        $this->firstFilesystem->write('location.txt', 'contents');

        $lastModified = $this->mountManager->lastModified('first://location.txt');
        $fileSize = $this->mountManager->fileSize('first://location.txt');
        $mimeType = $this->mountManager->mimeType('first://location.txt');

        $this->assertGreaterThanOrEqual($now, $lastModified);
        $this->assertEquals(8, $fileSize);
        $this->assertEquals('text/plain', $mimeType);
    }

    /**
     * @test
     */
    public function creating_a_directory(): void
    {
        $this->mountManager->createDirectory('first://directory');

        $directoryListing = $this->firstFilesystem->listContents('/')
            ->toArray();

        $this->assertCount(1, $directoryListing);
        /** @var DirectoryAttributes $directory */
        $directory = $directoryListing[0];
        $this->assertInstanceOf(DirectoryAttributes::class, $directory);
        $this->assertEquals('directory', $directory->path());
    }

    /**
     * @test
     */
    public function list_directory(): void
    {
        $this->mountManager->createDirectory('first://directory');
        $this->mountManager->write('first://directory/file', 'foo');

        $directoryListing = $this->mountManager->listContents('first://', Filesystem::LIST_DEEP)->toArray();

        $this->assertCount(2, $directoryListing);

        /** @var DirectoryAttributes $directory */
        $directory = $directoryListing[0];
        $this->assertInstanceOf(DirectoryAttributes::class, $directory);
        $this->assertEquals('first://directory', $directory->path());

        /** @var FileAttributes $file */
        $file = $directoryListing[1];
        $this->assertInstanceOf(FileAttributes::class, $file);
        $this->assertEquals('first://directory/file', $file->path());
    }

    /**
     * @test
     */
    public function copying_in_the_same_filesystem(): void
    {
        $this->firstFilesystem->write('location.txt', 'contents');
        $this->assertTrue($this->firstFilesystem->fileExists('location.txt'));

        $this->mountManager->copy('first://location.txt', 'first://new-location.txt');

        $this->assertTrue($this->firstFilesystem->fileExists('location.txt'));
        $this->assertTrue($this->firstFilesystem->fileExists('new-location.txt'));
    }

    /**
     * @test
     */
    public function failing_to_copy_in_the_same_filesystem(): void
    {
        $this->firstFilesystem->write('location.txt', 'contents');
        $this->firstStubAdapter->stageException('copy', 'location.txt', UnableToCopyFile::fromLocationTo('a', 'b'));

        $this->expectException(UnableToCopyFile::class);

        $this->mountManager->copy('first://location.txt', 'first://new-location.txt');
    }

    /**
     * @test
     */
    public function failing_to_move_in_the_same_filesystem(): void
    {
        $this->firstFilesystem->write('location.txt', 'contents');
        $this->firstStubAdapter->stageException('move', 'location.txt', UnableToMoveFile::fromLocationTo('a', 'b'));

        $this->expectException(UnableToMoveFile::class);

        $this->mountManager->move('first://location.txt', 'first://new-location.txt');
    }

    /**
     * @test
     */
    public function moving_in_the_same_filesystem(): void
    {
        $this->firstFilesystem->write('location.txt', 'contents');
        $this->assertTrue($this->firstFilesystem->fileExists('location.txt'));

        $this->mountManager->move('first://location.txt', 'first://new-location.txt');

        $this->assertFalse($this->firstFilesystem->fileExists('location.txt'));
        $this->assertTrue($this->firstFilesystem->fileExists('new-location.txt'));
    }

    /**
     * @test
     */
    public function moving_across_filesystem(): void
    {
        $this->firstFilesystem->write('location.txt', 'contents');
        $this->assertTrue($this->firstFilesystem->fileExists('location.txt'));

        $this->mountManager->move('first://location.txt', 'second://new-location.txt');

        $this->assertFalse($this->firstFilesystem->fileExists('location.txt'));
        $this->assertTrue($this->secondFilesystem->fileExists('new-location.txt'));
    }

    /**
     * @test
     */
    public function failing_to_move_across_filesystem(): void
    {
        $this->firstFilesystem->write('location.txt', 'contents');

        $this->firstStubAdapter->stageException('visibility', 'location.txt', UnableToRetrieveMetadata::visibility('location.txt'));

        $this->expectException(UnableToMoveFile::class);

        $this->mountManager->move('first://location.txt', 'second://new-location.txt');
    }

    /**
     * @test
     */
    public function failing_to_copy_across_filesystem(): void
    {
        $this->firstFilesystem->write('location.txt', 'contents');

        $this->firstStubAdapter->stageException('visibility', 'location.txt', UnableToRetrieveMetadata::visibility('location.txt'));

        $this->expectException(UnableToCopyFile::class);

        $this->mountManager->copy('first://location.txt', 'second://new-location.txt');
    }

    /**
     * @test
     */
    public function listing_contents(): void
    {
        $this->firstFilesystem->write('contents.txt', 'file contents');
        $this->firstFilesystem->write('dirname/contents.txt', 'file contents');
        $this->secondFilesystem->write('dirname/contents.txt', 'file contents');

        $contents = $this->mountManager->listContents('first://', FilesystemReader::LIST_DEEP)->toArray();

        $this->assertCount(3, $contents);
    }

    /**
     * @test
     */
    public function guarding_against_valid_mount_identifiers(): void
    {
        $this->expectException(UnableToMountFilesystem::class);

        /* @phpstan-ignore-next-line */
        new MountManager([1 => new Filesystem(new InMemoryFilesystemAdapter())]);
    }

    /**
     * @test
     */
    public function guarding_against_mounting_invalid_filesystems(): void
    {
        $this->expectException(UnableToMountFilesystem::class);

        /* @phpstan-ignore-next-line */
        new MountManager(['valid' => 'something else']);
    }

    /**
     * @test
     */
    public function guarding_against_using_paths_without_mount_prefix(): void
    {
        $this->expectException(UnableToResolveFilesystemMount::class);

        $this->mountManager->read('path-without-mount-prefix.txt');
    }

    /**
     * @test
     */
    public function guard_against_using_unknown_mount(): void
    {
        $this->expectException(UnableToResolveFilesystemMount::class);

        $this->mountManager->read('unknown://location.txt');
    }

    /**
     * @test
     */
    public function generate_public_url(): void
    {
        $mountManager = new MountManager([
            'first' => new Filesystem($this->firstStubAdapter, ['public_url' => 'first.example.com']),
            'second' => new Filesystem($this->secondStubAdapter, ['public_url' => 'second.example.com']),
        ]);

        $mountManager->write('first://file1.txt', 'content');
        $mountManager->write('second://file2.txt', 'content');

        $this->assertSame('first.example.com/file1.txt', $mountManager->publicUrl('first://file1.txt'));
        $this->assertSame('second.example.com/file2.txt', $mountManager->publicUrl('second://file2.txt'));
    }

    /**
     * @test
     */
    public function provide_checksum(): void
    {
        $this->mountManager->write('first://file.txt', 'content');

        $this->assertSame('9a0364b9e99bb480dd25e1f0284c8555', $this->mountManager->checksum('first://file.txt'));
    }
}
