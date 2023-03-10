<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV3;

use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use phpseclib3\Net\SFTP;

use function class_exists;

/**
 * @group sftp
 * @group phpseclib3
 */
class SftpAdapterTest extends FilesystemAdapterTestCase
{
    public static function setUpBeforeClass(): void
    {
        if ( ! class_exists(SFTP::class)) {
            self::markTestIncomplete("No phpseclib v3 installed");
        }
    }

    /**
     * @var ConnectionProvider
     */
    private static $connectionProvider;

    /**
     * @var SftpStub
     */
    private $connection;

    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        return new SftpAdapter(
            static::connectionProvider(),
            '/upload'
        );
    }

    /**
     * @before
     */
    public function setupConnectionProvider(): void
    {
        /** @var SftpStub $connection */
        $connection = static::connectionProvider()->provideConnection();
        $this->connection = $connection;
        $this->connection->reset();
    }

    /**
     * @test
     */
    public function failing_to_create_a_directory(): void
    {
        $adapter = $this->adapterWithInvalidRoot();

        $this->expectException(UnableToCreateDirectory::class);

        $adapter->createDirectory('not-gonna-happen', new Config());
    }

    /**
     * @test
     */
    public function failing_to_write_a_file(): void
    {
        $adapter = $this->adapterWithInvalidRoot();

        $this->expectException(UnableToWriteFile::class);

        $adapter->write('not-gonna-happen', 'na-ah', new Config());
    }

    /**
     * @test
     */
    public function failing_to_read_a_file(): void
    {
        $adapter = $this->adapterWithInvalidRoot();

        $this->expectException(UnableToReadFile::class);

        $adapter->read('not-gonna-happen');
    }

    /**
     * @test
     */
    public function failing_to_read_a_file_as_a_stream(): void
    {
        $adapter = $this->adapterWithInvalidRoot();

        $this->expectException(UnableToReadFile::class);

        $adapter->readStream('not-gonna-happen');
    }

    /**
     * @test
     */
    public function failing_to_write_a_file_using_streams(): void
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
    public function detecting_mimetype(): void
    {
        $adapter = $this->adapter();
        $adapter->write('file.svg', (string) file_get_contents(__DIR__ . '/../AdapterTestUtilities/test_files/flysystem.svg'), new Config());

        $mimeType = $adapter->mimeType('file.svg');

        $this->assertStringStartsWith('image/svg+xml', $mimeType->mimeType());
    }

    /**
     * @test
     */
    public function failing_to_chmod_when_writing(): void
    {
        $this->connection->failOnChmod('/upload/path.txt');
        $adapter = $this->adapter();

        $this->expectException(UnableToWriteFile::class);

        $adapter->write('path.txt', 'contents', new Config(['visibility' => 'public']));
    }

    /**
     * @test
     */
    public function failing_to_move_a_file_cause_the_parent_directory_cant_be_created(): void
    {
        $adapter = $this->adapterWithInvalidRoot();

        $this->expectException(UnableToMoveFile::class);

        $adapter->move('path.txt', 'new-path.txt', new Config());
    }

    /**
     * @test
     */
    public function failing_to_copy_a_file(): void
    {
        $adapter = $this->adapterWithInvalidRoot();

        $this->expectException(UnableToCopyFile::class);

        $adapter->copy('path.txt', 'new-path.txt', new Config());
    }

    /**
     * @test
     */
    public function failing_to_copy_a_file_because_writing_fails(): void
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
    public function failing_to_chmod_when_writing_with_a_stream(): void
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

    /**
     * @test
     */
    public function list_contents_directory_does_not_exist(): void
    {
        $contents = $this->adapter()->listContents('/does_not_exist', false);
        $this->assertCount(0, iterator_to_array($contents));
    }

    private static function connectionProvider(): ConnectionProvider
    {
        if ( ! static::$connectionProvider instanceof ConnectionProvider) {
            static::$connectionProvider = new StubSftpConnectionProvider('localhost', 'foo', 'pass', 2222);
        }

        return static::$connectionProvider;
    }

    /**
     * @return SftpAdapter
     */
    private function adapterWithInvalidRoot(): SftpAdapter
    {
        $provider = static::connectionProvider();
        $adapter = new SftpAdapter($provider, '/invalid');

        return $adapter;
    }
}
