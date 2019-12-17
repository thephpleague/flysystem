<?php

declare(strict_types=1);

namespace League\Flysystem\Local;

use League\Flysystem\Config;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\Visibility;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function file_put_contents;
use function fileperms;
use function fwrite;
use function is_dir;
use function rewind;

class LocalFilesystemTest extends TestCase
{
    private const ROOT = __DIR__ . '/test-root';

    protected function setUp(): void
    {
        $this->deleteDirectory(static::ROOT);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory(static::ROOT);
    }

    private function deleteDirectory(string $dir)
    {
        if ( ! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }
            if (is_dir("$dir/$file")) {
                $this->deleteDirectory("$dir/$file");
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
            static::ROOT,
            new PublicAndPrivateVisibilityInterpreting()
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
    public function not_being_able_to_write_a_file()
    {
        $this->expectException(UnableToDeleteFile::class);
        (new LocalFilesystem('/'))->write('/cannot-create-a-file-here', 'contents', new Config());
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
}
