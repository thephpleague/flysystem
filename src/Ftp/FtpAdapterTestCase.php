<?php

declare(strict_types=1);

namespace League\Flysystem\Ftp;

use Generator;
use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;

/**
 * @group ftp
 */
abstract class FtpAdapterTestCase extends FilesystemAdapterTestCase
{
    /**
     * @var ConnectivityCheckerThatCanFail
     */
    protected static $connectivityChecker;

    /**
     * @after
     */
    public function resetFunctionMocks(): void
    {
        reset_function_mocks();
    }

    /**
     * @test
     */
    public function reconnecting_after_failure(): void
    {
        $adapter = $this->adapter();
        static::$connectivityChecker->failNextCall();

        $contents = iterator_to_array($adapter->listContents('', false));
        $this->assertIsArray($contents);
    }

    /**
     * @test
     * @dataProvider scenariosCausingWriteFailure
     */
    public function failing_to_write_a_file(callable $scenario): void
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
    public function scenarios_causing_directory_deletion_to_fail(callable $scenario): void
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
    public function failing_to_copy(callable $scenario): void
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
    public function failing_to_move_because_creating_the_directory_fails(): void
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
    public function failing_to_delete_a_file(): void
    {
        $this->givenWeHaveAnExistingFile('path.txt', 'contents');
        $adapter = $this->adapter();
        mock_function('ftp_delete', false);

        $this->expectException(UnableToDeleteFile::class);

        $adapter->delete('path.txt');
    }

    /**
     * @test
     */
    public function formatting_a_directory_listing_with_a_total_indicator(): void
    {
        $response = [
            'total 1',
            '-rw-r--r--   1 ftp      ftp           409 Aug 19 09:01 file1.txt',
        ];
        mock_function('ftp_rawlist', $response);

        $adapter = $this->adapter();
        $contents = iterator_to_array($adapter->listContents('/', false), false);

        $this->assertCount(1, $contents);
        $this->assertContainsOnlyInstancesOf(FileAttributes::class, $contents);
    }

    /**
     * @test
     */
    public function receiving_a_windows_listing(): void
    {
        $response = [
            '2015-05-23  12:09       <DIR>          dir1',
            '05-23-15  12:09PM                  684 file2.txt',
        ];
        mock_function('ftp_rawlist', $response);

        $adapter = $this->adapter();
        $contents = iterator_to_array($adapter->listContents('/', false), false);

        $this->assertCount(2, $contents);
        $this->assertContainsOnlyInstancesOf(StorageAttributes::class, $contents);
    }

    /**
     * @test
     */
    public function receiving_an_invalid_windows_listing(): void
    {
        $response = [
            '05-23-15  12:09PM    file2.txt',
        ];
        mock_function('ftp_rawlist', $response);


        $this->expectException(InvalidListResponseReceived::class);

        $adapter = $this->adapter();
        iterator_to_array($adapter->listContents('/', false), false);
}

    /**
     * @test
     */
    public function getting_an_invalid_listing_response_for_unix_listings(): void
    {
        $response = [
            'total 1',
            '-rw-r--r--   1 ftp           409 Aug 19 09:01 file1.txt',
        ];
        mock_function('ftp_rawlist', $response);

        $this->expectException(InvalidListResponseReceived::class);

        $adapter = $this->adapter();
        iterator_to_array($adapter->listContents('/', false), false);
    }

    /**
     * @test
     */
    public function formatting_non_manual_recursive_listings(): void
    {
        $response = [
            'drwxr-xr-x   4 ftp      ftp          4096 Nov 24 13:58 .',
            'drwxr-xr-x  16 ftp      ftp          4096 Sep  2 13:01 ..',
            'drwxr-xr-x   2 ftp      ftp          4096 Oct 13  2012 cgi-bin',
            'drwxr-xr-x   2 ftp      ftp          4096 Nov 24 13:59 folder',
            '-rw-r--r--   1 ftp      ftp           409 Oct 13  2012 index.html',
            '',
            'somewhere/cgi-bin:',
            'drwxr-xr-x   2 ftp      ftp          4096 Oct 13  2012 .',
            'drwxr-xr-x   4 ftp      ftp          4096 Nov 24 13:58 ..',
            '',
            'somewhere/folder:',
            'drwxr-xr-x   2 ftp      ftp          4096 Nov 24 13:59 .',
            'drwxr-xr-x   4 ftp      ftp          4096 Nov 24 13:58 ..',
            '-rw-r--r--   1 ftp      ftp             0 Nov 24 13:59 dummy.txt',
        ];

        mock_function('ftp_rawlist', $response);

        $options = FtpConnectionOptions::fromArray([
           'host' => 'localhost',
           'port' => 2121,
           'timestampsOnUnixListingsEnabled' => true,
           'recurseManually' => false,
           'root' => '/home/foo/upload/',
           'username' => 'foo',
           'password' => 'pass',
       ]);

        $adapter = new FtpAdapter($options);

        $contents = iterator_to_array($adapter->listContents('somewhere', true), false);

        $this->assertCount(4, $contents);
        $this->assertContainsOnlyInstancesOf(StorageAttributes::class, $contents);
    }

    /**
     * @test
     */
    public function filenames_and_dirnames_with_spaces_are_supported(): void
    {
        $this->givenWeHaveAnExistingFile('some dirname/file name.txt');
        $adapter = $this->adapter();

        $this->assertTrue($adapter->fileExists('some dirname/file name.txt'));
        $contents = iterator_to_array($adapter->listContents('', true));
        $this->assertCount(2, $contents);
        $this->assertContainsOnlyInstancesOf(StorageAttributes::class, $contents);
    }
}
