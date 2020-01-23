<?php

declare(strict_types=1);

namespace League\Flysystem\PHPSecLibV2;

use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemAdapterTestCase;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;

class SftpFilesystemTest extends FilesystemAdapterTestCase
{
    /**
     * @var SftpConnectionProvider
     */
    private $connectionProvider;

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

    private function connectionProvider(): ConnectionProvider
    {
        if ( ! $this->connectionProvider instanceof SftpConnectionProvider) {
            $this->connectionProvider = new SftpConnectionProvider('localhost', 'foo', 'pass', 2222);
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
