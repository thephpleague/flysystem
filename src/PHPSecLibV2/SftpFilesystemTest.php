<?php

declare(strict_types=1);

namespace League\Flysystem\PHPSecLibV2;

use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemAdapterTestCase;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;

/**
 * @group sftp
 */
class SftpFilesystemTest extends FilesystemAdapterTestCase
{
    /**
     * @var SftpConnectionProvider
     */
    private $connectionProvider;

    /**
     * @var SFTPStub
     */
    private $connection;

    protected function createFilesystemAdapter(): FilesystemAdapter
    {
        if (getenv('FLYSYSTEM_TEST_SFTP') !== 'yes') {
            $this->markTestSkipped('Opted out of testing SFTP');
        }

        return new SftpFilesystem(
            $this->connectionProvider(),
            '/upload'
        );
    }

    /**
     * @before
     */
    public function setupConnectionProvider(): void
    {
        $this->connection = $this->connectionProvider()->provideConnection();
    }

    /**
     * @test
     */
    public function failing_to_create_a_directory()
    {
        $adapter = $this->adapterWithInvalidRoot();

        $this->expectException(UnableToCreateDirectory::class);

        $adapter->createDirectory('not-gonna-happen', new Config());
    }

    /**
     * @test
     */
    public function failing_to_write_a_file()
    {
        $adapter = $this->adapterWithInvalidRoot();

        $this->expectException(UnableToWriteFile::class);

        $adapter->write('not-gonna-happen', 'na-ah', new Config());
    }

    /**
     * @test
     */
    public function failing_to_read_a_file()
    {
        $adapter = $this->adapterWithInvalidRoot();

        $this->expectException(UnableToReadFile::class);

        $adapter->read('not-gonna-happen');
    }

    /**
     * @test
     */
    public function failing_to_read_a_file_as_a_stream()
    {
        $adapter = $this->adapterWithInvalidRoot();

        $this->expectException(UnableToReadFile::class);

        $adapter->readStream('not-gonna-happen');
    }

    /**
     * @test
     */
    public function failing_to_write_a_file_using_streams()
    {
        $adapter = $this->adapterWithInvalidRoot();
        $writeHandle = stream_with_contents('contents');

        $this->expectException(UnableToWriteFile::class);

        try {
            $adapter->writeStream('not-gonna-happen', $writeHandle, new Config());
        } finally {
            fclose($writeHandle);
        }
    }

    /**
     * @test
     */
    public function detecting_mimetype()
    {
        $adapter = $this->adapter();
        $adapter->write('file.svg', file_get_contents(__DIR__.'/../../test_files/flysystem.svg'), new Config());

        $mimeType = $adapter->mimeType('file.svg');

        $this->assertEquals('image/svg', $mimeType->mimeType());
    }

    /**
     * @test
     */
    public function failing_to_chmod_when_writing()
    {
        $this->connection->failOnChmod('/upload/path.txt');
        $adapter = $this->adapter();

        $this->expectException(UnableToWriteFile::class);

        $adapter->write('path.txt', 'contents', new Config(['visibility' => 'public']));
    }

    /**
     * @test
     */
    public function failing_to_move_a_file_cause_the_parent_directory_cant_be_created()
    {
        $adapter = $this->adapterWithInvalidRoot();

        $this->expectException(UnableToMoveFile::class);

        $adapter->move('path.txt', 'new-path.txt', new Config());
    }

    /**
     * @test
     */
    public function failing_to_copy_a_file()
    {
        $adapter = $this->adapterWithInvalidRoot();

        $this->expectException(UnableToCopyFile::class);

        $adapter->copy('path.txt', 'new-path.txt', new Config());
    }

    /**
     * @test
     */
    public function failing_to_copy_a_file_because_writing_fails()
    {
        $this->givenWeHaveAnExistingFile('path.txt', 'contents');
        $adapter = $this->adapter();
        $this->connection->failOnPut('/upload/new-path.txt');

        $this->expectException(UnableToCopyFile::class);

        $adapter->copy('path.txt', 'new-path.txt', new Config());
    }

    /**
     * @test
     */
    public function failing_to_chmod_when_writing_with_a_stream()
    {
        $writeStream = stream_with_contents('contents');
        $this->connection->failOnChmod('/upload/path.txt');
        $adapter = $this->adapter();

        $this->expectException(UnableToWriteFile::class);

        try {
            $adapter->writeStream('path.txt', $writeStream, new Config(['visibility' => 'public']));
        } finally {
            @fclose($writeStream);
        }
    }

    private function connectionProvider(): ConnectionProvider
    {
        if ( ! $this->connectionProvider instanceof ConnectionProvider) {
            $this->connectionProvider = new StubSFTPConnectionProvider('localhost', 'foo', 'pass', 2222);
        }

        return $this->connectionProvider;
    }

    /**
     * @return SftpFilesystem
     */
    private function adapterWithInvalidRoot(): SftpFilesystem
    {
        $provider = $this->connectionProvider();
        $adapter = new SftpFilesystem($provider, '/invalid');

        return $adapter;
    }
}
