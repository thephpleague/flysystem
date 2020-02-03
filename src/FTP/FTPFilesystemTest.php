<?php

declare(strict_types=1);

namespace League\Flysystem\FTP;

use Generator;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemAdapterTestCase;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;

/**
 * @group ftp
 */
class FTPFilesystemTest extends FilesystemAdapterTestCase
{
    /**
     * @var ConnectivityCheckerThatCanFail
     */
    private $connectivityChecker;

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

        $this->connectivityChecker = new ConnectivityCheckerThatCanFail(new RawListFTPConnectivityChecker());

        return new FTPFilesystem($options, null, $this->connectivityChecker);
    }

    /**
     * @test
     */
    public function reconnecting_after_failure()
    {
        $adapter = $this->adapter();
        $this->connectivityChecker->failNextCall();

        $contents = iterator_to_array($adapter->listContents('', false));
        $this->assertIsArray($contents);
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
     * @dataProvider scenariosCausingCopyFailure
     */
    public function failing_to_copy(callable $scenario)
    {
        $adapter = $this->adapter();
        $this->givenWeHaveAnExistingFile('path.txt');
        $scenario();

        $this->expectException(UnableToCopyFile::class);

        $adapter->copy('path.txt', 'new/path.txt', new Config());
    }

    /**
     * @test
     */
    public function failing_to_move_because_creating_the_directory_fails()
    {
        $adapter = $this->adapter();
        $this->givenWeHaveAnExistingFile('path.txt');
        mock_function('ftp_mkdir', false);

        $this->expectException(UnableToMoveFile::class);

        $adapter->move('path.txt', 'new/path.txt', new Config());
    }

    public function scenariosCausingCopyFailure(): Generator
    {
        yield "failing to read" => [function() {
            mock_function('ftp_fget', false);
        }];

        yield "failing to write" => [function() {
            mock_function('ftp_fput', false);
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
