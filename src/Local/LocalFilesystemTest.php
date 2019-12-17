<?php

declare(strict_types=1);

namespace League\Flysystem\Local;

use League\Flysystem\Config;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToWriteFile;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function fileperms;
use function is_dir;
use function octdec;
use function sprintf;
use function substr;
use function var_dump;

use const LOCK_EX;

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
    public function writing_a_file_with_visibility()
    {
        $adapter = new LocalFilesystem(
            static::ROOT,
            PublicAndPrivateVisibilityInterpreting::fromArray(['file' => ['public' => 0644]])
        );
        $adapter->write('/file.txt', 'contents', new Config(['visibility' => 'private']));
        $this->assertFileExists(static::ROOT . '/file.txt');
        $contents = file_get_contents(static::ROOT . '/file.txt');
        $this->assertEquals('contents', $contents);

        clearstatcache(false, static::ROOT . '/file.txt');
        $permissions = fileperms(static::ROOT . '/file.txt') & 0777;
        $this->assertEquals(0600, $permissions);
    }

    /**
     * @test
     */
    public function not_being_able_to_write_a_file()
    {
        $this->expectException(UnableToWriteFile::class);
        (new LocalFilesystem('/'))->write('/cannot-create-a-file-here', 'contents', new Config());
    }
}
