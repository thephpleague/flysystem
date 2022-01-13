<?php

declare(strict_types=1);

namespace League\Flysystem;

use PHPUnit\Framework\TestCase;

/**
 * @group core
 */
class ExceptionInformationTest extends TestCase
{
    /**
     * @test
     */
    public function copy_exception_information(): void
    {
        $exception = UnableToCopyFile::fromLocationTo('from', 'to');
        $this->assertEquals('from', $exception->source());
        $this->assertEquals('to', $exception->destination());
        $this->assertEquals(FilesystemOperationFailed::OPERATION_COPY, $exception->operation());
    }

    /**
     * @test
     */
    public function create_directory_exception_information(): void
    {
        $exception = UnableToCreateDirectory::atLocation('from', 'some message');
        $this->assertEquals('from', $exception->location());
        $this->assertStringContainsString('some message', $exception->getMessage());
        $this->assertEquals(FilesystemOperationFailed::OPERATION_CREATE_DIRECTORY, $exception->operation());
    }

    /**
     * @test
     */
    public function delete_directory_exception_information(): void
    {
        $exception = UnableToDeleteDirectory::atLocation('from', 'some message');
        $this->assertEquals('some message', $exception->reason());
        $this->assertEquals('from', $exception->location());
        $this->assertStringContainsString('some message', $exception->getMessage());
        $this->assertEquals(FilesystemOperationFailed::OPERATION_DELETE_DIRECTORY, $exception->operation());
    }

    /**
     * @test
     */
    public function delete_file_exception_information(): void
    {
        $exception = UnableToDeleteFile::atLocation('from', 'some message');
        $this->assertEquals('from', $exception->location());
        $this->assertEquals('some message', $exception->reason());
        $this->assertStringContainsString('some message', $exception->getMessage());
        $this->assertEquals(FilesystemOperationFailed::OPERATION_DELETE, $exception->operation());
    }

    /**
     * @test
     */
    public function unable_to_check_for_file_existence(): void
    {
        $exception = UnableToCheckFileExistence::forLocation('location');
        $this->assertEquals(FilesystemOperationFailed::OPERATION_FILE_EXISTS, $exception->operation());
    }

    /**
     * @test
     */
    public function unable_to_check_for_existence(): void
    {
        $exception = UnableToCheckExistence::forLocation('location');
        $this->assertEquals(FilesystemOperationFailed::OPERATION_EXISTENCE_CHECK, $exception->operation());
    }

    /**
     * @test
     */
    public function unable_to_check_for_directory_existence(): void
    {
        $exception = UnableToCheckDirectoryExistence::forLocation('location');
        $this->assertEquals(FilesystemOperationFailed::OPERATION_DIRECTORY_EXISTS, $exception->operation());
    }

    /**
     * @test
     */
    public function move_file_exception_information(): void
    {
        $exception = UnableToMoveFile::fromLocationTo('from', 'to');
        $this->assertEquals('from', $exception->source());
        $this->assertEquals('to', $exception->destination());
        $this->assertEquals(FilesystemOperationFailed::OPERATION_MOVE, $exception->operation());
    }

    /**
     * @test
     */
    public function read_file_exception_information(): void
    {
        $exception = UnableToReadFile::fromLocation('from', 'some message');
        $this->assertEquals('from', $exception->location());
        $this->assertEquals('some message', $exception->reason());
        $this->assertStringContainsString('some message', $exception->getMessage());
        $this->assertEquals(FilesystemOperationFailed::OPERATION_READ, $exception->operation());
    }

    /**
     * @test
     */
    public function retrieve_visibility_exception_information(): void
    {
        $exception = UnableToRetrieveMetadata::visibility('from', 'some message');
        $this->assertEquals('from', $exception->location());
        $this->assertEquals(FileAttributes::ATTRIBUTE_VISIBILITY, $exception->metadataType());
        $this->assertStringContainsString('some message', $exception->getMessage());
        $this->assertEquals(FilesystemOperationFailed::OPERATION_RETRIEVE_METADATA, $exception->operation());
    }

    /**
     * @test
     */
    public function set_visibility_exception_information(): void
    {
        $exception = UnableToSetVisibility::atLocation('from', 'some message');
        $this->assertEquals('from', $exception->location());
        $this->assertEquals('some message', $exception->reason());
        $this->assertStringContainsString('some message', $exception->getMessage());
        $this->assertEquals(FilesystemOperationFailed::OPERATION_SET_VISIBILITY, $exception->operation());
    }

    /**
     * @test
     */
    public function write_file_exception_information(): void
    {
        $exception = UnableToWriteFile::atLocation('from', 'some message');
        $this->assertEquals('from', $exception->location());
        $this->assertEquals('some message', $exception->reason());
        $this->assertStringContainsString('some message', $exception->getMessage());
        $this->assertEquals(FilesystemOperationFailed::OPERATION_WRITE, $exception->operation());
    }

    /**
     * @test
     */
    public function unreadable_file_exception_information(): void
    {
        $exception = UnreadableFileEncountered::atLocation('the-location');
        $this->assertEquals('the-location', $exception->location());
        $this->assertStringContainsString('the-location', $exception->getMessage());
    }

    /**
     * @test
     */
    public function symbolic_link_exception_information(): void
    {
        $exception = SymbolicLinkEncountered::atLocation('the-location');
        $this->assertEquals('the-location', $exception->location());
        $this->assertStringContainsString('the-location', $exception->getMessage());
    }

    /**
     * @test
     */
    public function path_traversal_exception_information(): void
    {
        $exception = PathTraversalDetected::forPath('../path.txt');
        $this->assertEquals('../path.txt', $exception->path());
        $this->assertStringContainsString('../path.txt', $exception->getMessage());
    }
}
