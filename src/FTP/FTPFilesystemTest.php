<?php

declare(strict_types=1);

namespace League\Flysystem\FTP;

use Generator;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemAdapterTestCase;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;

class FTPFilesystemTest extends FilesystemAdapterTestCase
{
    protected function createFilesystemAdapter(): FilesystemAdapter
    {
        $options = FTPConnectionOptions::fromArray([
            'host' => 'localhost',
            'port' => 2121,
            'timestampsOnUnixListingsEnabled' => true,
            'recurseManually' => true,
            'root' => '/home/foo/upload/',
            'username' => 'foo',
            'password' => 'pass',
        ]);

        return new FTPFilesystem($options);
    }

    /**
     * @test
     * @dataProvider scenariosCausingWriteFailure
     */
    public function failing_to_write_a_file(callable $scenario)
    {
        $scenario();
        $adapter = $this->adapter();

        $this->expectException(UnableToWriteFile::class);

        $adapter->write('some/path.txt', 'contents', new Config([
            Config::OPTION_VISIBILITY => Visibility::PUBLIC,
            Config::OPTION_DIRECTORY_VISIBILITY => Visibility::PUBLIC
        ]));
    }

    public function scenariosCausingWriteFailure(): Generator
    {
        yield "Not being able to create the parent directory" => [function() {
            mock_function('ftp_mkdir', false);
        }];

        yield "Not being able to set the parent directory visibility" => [function() {
            mock_function('ftp_chmod', false);
        }];

        yield "Not being able to write the file" => [function() {
            mock_function('ftp_fput', false);
        }];

        yield "Not being able to set the visibility" => [function() {
            mock_function('ftp_chmod', true, false);
        }];
    }



    /**
     * @test
     * @dataProvider scenariosCausingDirectoryDeleteFailure
     */
    public function scenarios_causing_directory_deletion_to_fail(callable $scenario)
    {
        $scenario();
        $adapter = $this->adapter();
        $this->givenWeHaveAnExistingFile('some/nested/path.txt');

        $this->expectException(UnableToDeleteDirectory::class);

        $adapter->deleteDirectory('some');
    }

    public function scenariosCausingDirectoryDeleteFailure(): Generator
    {
        yield "ftp_delete failure" => [function() {
            mock_function('ftp_delete', false);
        }];

        yield "ftp_rmdir failure" => [function() {
            mock_function('ftp_rmdir', false);
        }];
    }

    /**
     * @test
     */
    public function failing_to_delete_a_file()
    {
        $this->givenWeHaveAnExistingFile('path.txt', 'contents');
        $adapter = $this->adapter();
        mock_function('ftp_delete', false);

        $this->expectException(UnableToDeleteFile::class);

        $adapter->delete('path.txt');
    }
}
