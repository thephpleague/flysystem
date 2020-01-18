<?php

declare(strict_types=1);

namespace League\Flysystem;

use IteratorAggregate;
use League\Flysystem\InMemory\InMemoryFilesystem;
use PHPUnit\Framework\TestCase;

class FilesystemTest extends TestCase
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @before
     */
    public function setupFilesystem(): void
    {
        $adapter = new InMemoryFilesystem();
        $filesystem = new Filesystem($adapter);
        $this->filesystem = $filesystem;
    }

    /**
     * @test
     */
    public function writing_and_reading_files()
    {
        $this->filesystem->write('path.txt', 'contents');
        $contents = $this->filesystem->read('path.txt');

        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function updating_and_reading_files()
    {
        $this->filesystem->update('path.txt', 'contents');
        $contents = $this->filesystem->read('path.txt');

        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function writing_and_reading_a_stream()
    {
        $writeStream = stream_with_contents('contents');

        $this->filesystem->writeStream('path.txt', $writeStream);
        $readStream = $this->filesystem->readStream('path.txt');

        fclose($writeStream);

        $this->assertIsResource($readStream);
        $this->assertEquals('contents', stream_get_contents($readStream));

        fclose($readStream);
    }

    /**
     * @test
     */
    public function updating_and_reading_a_stream()
    {
        $writeStream = stream_with_contents('contents');

        $this->filesystem->updateStream('path.txt', $writeStream);
        $readStream = $this->filesystem->readStream('path.txt');

        fclose($writeStream);

        $this->assertIsResource($readStream);
        $this->assertEquals('contents', stream_get_contents($readStream));

        fclose($readStream);
    }

    /**
     * @test
     */
    public function checking_if_files_exist()
    {
        $this->filesystem->write('path.txt', 'contents');

        $pathDotTxtExists = $this->filesystem->fileExists('path.txt');
        $otherFileExists = $this->filesystem->fileExists('other.txt');

        $this->assertTrue($pathDotTxtExists);
        $this->assertFalse($otherFileExists);
    }

    /**
     * @test
     */
    public function deleting_a_file()
    {
        $this->filesystem->write('path.txt', 'content');
        $this->filesystem->delete('path.txt');

        $this->assertFalse($this->filesystem->fileExists('path.txt'));
    }

    /**
     * @test
     */
    public function deleting_a_directory()
    {
        $this->filesystem->write('dirname/a.txt', 'contents');
        $this->filesystem->write('dirname/b.txt', 'contents');
        $this->filesystem->write('dirname/c.txt', 'contents');

        $this->filesystem->deleteDirectory('dir');

        $this->assertTrue($this->filesystem->fileExists('dirname/a.txt'));

        $this->filesystem->deleteDirectory('dirname');

        $this->assertFalse($this->filesystem->fileExists('dirname/a.txt'));
        $this->assertFalse($this->filesystem->fileExists('dirname/b.txt'));
        $this->assertFalse($this->filesystem->fileExists('dirname/c.txt'));
    }

    /**
     * @test
     */
    public function creating_a_directory()
    {
        $this->filesystem->createDirectory('path/here');

        $this->expectNotToPerformAssertions();
    }

    /**
     * @test
     */
    public function listing_directory_contents()
    {
        $this->filesystem->write('dirname/a.txt', 'contents');
        $this->filesystem->write('dirname/b.txt', 'contents');
        $this->filesystem->write('dirname/c.txt', 'contents');

        $listing = $this->filesystem->listContents('', false);

        $this->assertInstanceOf(DirectoryListing::class, $listing);
        $this->assertInstanceOf(IteratorAggregate::class, $listing);

        $attributeListing = iterator_to_array($listing);
        $this->assertContainsOnlyInstancesOf(StorageAttributes::class, $attributeListing);
        $this->assertCount(1, $attributeListing);
    }

    /**
     * @test
     */
    public function listing_directory_contents_recursive()
    {
        $this->filesystem->write('dirname/a.txt', 'contents');
        $this->filesystem->write('dirname/b.txt', 'contents');
        $this->filesystem->write('dirname/c.txt', 'contents');

        $listing = $this->filesystem->listContents('', true);

        $attributeListing = $listing->toArray();
        $this->assertContainsOnlyInstancesOf(StorageAttributes::class, $attributeListing);
        $this->assertCount(4, $attributeListing);
    }

    /**
     * @test
     */
    public function copying_files()
    {
        $this->filesystem->write('path.txt', 'contents');

        $this->filesystem->copy('path.txt', 'new-path.txt');

        $this->assertTrue($this->filesystem->fileExists('path.txt'));
        $this->assertTrue($this->filesystem->fileExists('new-path.txt'));
    }

    /**
     * @test
     */
    public function moving_files()
    {
        $this->filesystem->write('path.txt', 'contents');

        $this->filesystem->move('path.txt', 'new-path.txt');

        $this->assertFalse($this->filesystem->fileExists('path.txt'));
        $this->assertTrue($this->filesystem->fileExists('new-path.txt'));
    }

    /**
     * @test
     */
    public function last_modified_getter()
    {
        $this->filesystem->write('path.txt', 'contents');

        $lastModified = $this->filesystem->lastModified('path.txt');

        $this->assertIsInt($lastModified);
        $this->assertTrue($lastModified > time() - 30);
        $this->assertTrue($lastModified < time() + 30);
    }
}
