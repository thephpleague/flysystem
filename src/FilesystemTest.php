<?php

declare(strict_types=1);

namespace League\Flysystem;

use Aws\S3\S3Client;
use Generator;
use IteratorAggregate;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

/**
 * @group core
 */
class FilesystemTest extends TestCase
{
    const ROOT = __DIR__ . '/../test_files/test-root';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @before
     */
    public function setupFilesystem(): void
    {
        $adapter = new LocalFilesystemAdapter(self::ROOT);
        $filesystem = new Filesystem($adapter);
        $this->filesystem = $filesystem;
    }

    /**
     * @after
     */
    public function removeFiles(): void
    {
        delete_directory(static::ROOT);
    }

    /**
     * @test
     */
    public function writing_and_reading_files(): void
    {
        $this->filesystem->write('path.txt', 'contents');
        $contents = $this->filesystem->read('path.txt');

        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     * @dataProvider invalidStreamInput
     *
     * @param mixed $input
     */
    public function trying_to_write_with_an_invalid_stream_arguments($input): void
    {
        $this->expectException(InvalidStreamProvided::class);

        $this->filesystem->writeStream('path.txt', $input);
    }

    public function invalidStreamInput(): Generator
    {
        $handle = tmpfile();
        fclose($handle);
        yield "resource that is not open" => [$handle];
        yield "something that is not a resource" => [false];
    }

    /**
     * @test
     */
    public function writing_and_reading_a_stream(): void
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
    public function checking_if_files_exist(): void
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
    public function checking_if_directories_exist(): void
    {
        $this->filesystem->createDirectory('existing-directory');

        $existingDirectory = $this->filesystem->directoryExists('existing-directory');
        $notExistingDirectory = $this->filesystem->directoryExists('not-existing-directory');

        $this->assertTrue($existingDirectory);
        $this->assertFalse($notExistingDirectory);
    }

    /**
     * @test
     */
    public function deleting_a_file(): void
    {
        $this->filesystem->write('path.txt', 'content');
        $this->filesystem->delete('path.txt');

        $this->assertFalse($this->filesystem->fileExists('path.txt'));
    }

    /**
     * @test
     */
    public function creating_a_directory(): void
    {
        $this->filesystem->createDirectory('here');

        $directoryAttrs = $this->filesystem->listContents('')->toArray()[0];
        $this->assertInstanceOf(DirectoryAttributes::class, $directoryAttrs);
        $this->assertEquals('here', $directoryAttrs->path());
    }

    /**
     * @test
     */
    public function deleting_a_directory(): void
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
    public function listing_directory_contents(): void
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
    public function listing_directory_contents_recursive(): void
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
    public function copying_files(): void
    {
        $this->filesystem->write('path.txt', 'contents');

        $this->filesystem->copy('path.txt', 'new-path.txt');

        $this->assertTrue($this->filesystem->fileExists('path.txt'));
        $this->assertTrue($this->filesystem->fileExists('new-path.txt'));
    }

    /**
     * @test
     */
    public function moving_files(): void
    {
        $this->filesystem->write('path.txt', 'contents');

        $this->filesystem->move('path.txt', 'new-path.txt');

        $this->assertFalse($this->filesystem->fileExists('path.txt'));
        $this->assertTrue($this->filesystem->fileExists('new-path.txt'));
    }

    /**
     * @test
     */
    public function fetching_last_modified(): void
    {
        $this->filesystem->write('path.txt', 'contents');

        $lastModified = $this->filesystem->lastModified('path.txt');

        $this->assertIsInt($lastModified);
        $this->assertTrue($lastModified > time() - 30);
        $this->assertTrue($lastModified < time() + 30);
    }

    /**
     * @test
     */
    public function fetching_mime_type(): void
    {
        $this->filesystem->write('path.txt', 'contents');

        $mimeType = $this->filesystem->mimeType('path.txt');

        $this->assertEquals('text/plain', $mimeType);
    }

    /**
     * @test
     */
    public function fetching_file_size(): void
    {
        $this->filesystem->write('path.txt', 'contents');

        $fileSize = $this->filesystem->fileSize('path.txt');

        $this->assertEquals(8, $fileSize);
    }

    /**
     * @test
     */
    public function ensuring_streams_are_rewound_when_writing(): void
    {
        $writeStream = stream_with_contents('contents');
        fseek($writeStream, 4);

        $this->filesystem->writeStream('path.txt', $writeStream);
        $contents = $this->filesystem->read('path.txt');

        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function setting_visibility(): void
    {
        $this->filesystem->write('path.txt', 'contents');

        $this->filesystem->setVisibility('path.txt', Visibility::PUBLIC);
        $publicVisibility = $this->filesystem->visibility('path.txt');

        $this->filesystem->setVisibility('path.txt', Visibility::PRIVATE);
        $privateVisibility = $this->filesystem->visibility('path.txt');

        $this->assertEquals(Visibility::PUBLIC, $publicVisibility);
        $this->assertEquals(Visibility::PRIVATE, $privateVisibility);
    }

    /**
     * @test
     * @dataProvider scenariosCausingPathTraversal
     */
    public function protecting_against_path_traversals(callable $scenario): void
    {
        $this->expectException(PathTraversalDetected::class);
        $scenario($this->filesystem);
    }

    public function scenariosCausingPathTraversal(): Generator
    {
        yield [function (FilesystemOperator $filesystem) {
            $filesystem->delete('../path.txt');
        }];
        yield [function (FilesystemOperator $filesystem) {
            $filesystem->deleteDirectory('../path');
        }];
        yield [function (FilesystemOperator $filesystem) {
            $filesystem->createDirectory('../path');
        }];
        yield [function (FilesystemOperator $filesystem) {
            $filesystem->read('../path.txt');
        }];
        yield [function (FilesystemOperator $filesystem) {
            $filesystem->readStream('../path.txt');
        }];
        yield [function (FilesystemOperator $filesystem) {
            $filesystem->write('../path.txt', 'contents');
        }];
        yield [function (FilesystemOperator $filesystem) {
            $stream = stream_with_contents('contents');
            try {
                $filesystem->writeStream('../path.txt', $stream);
            } finally {
                fclose($stream);
            }
        }];
        yield [function (FilesystemOperator $filesystem) {
            $filesystem->listContents('../path');
        }];
        yield [function (FilesystemOperator $filesystem) {
            $filesystem->fileExists('../path.txt');
        }];
        yield [function (FilesystemOperator $filesystem) {
            $filesystem->mimeType('../path.txt');
        }];
        yield [function (FilesystemOperator $filesystem) {
            $filesystem->fileSize('../path.txt');
        }];
        yield [function (FilesystemOperator $filesystem) {
            $filesystem->lastModified('../path.txt');
        }];
        yield [function (FilesystemOperator $filesystem) {
            $filesystem->visibility('../path.txt');
        }];
        yield [function (FilesystemOperator $filesystem) {
            $filesystem->setVisibility('../path.txt', Visibility::PUBLIC);
        }];
        yield [function (FilesystemOperator $filesystem) {
            $filesystem->copy('../path.txt', 'path.txt');
        }];
        yield [function (FilesystemOperator $filesystem) {
            $filesystem->copy('path.txt', '../path.txt');
        }];
        yield [function (FilesystemOperator $filesystem) {
            $filesystem->move('../path.txt', 'path.txt');
        }];
        yield [function (FilesystemOperator $filesystem) {
            $filesystem->move('path.txt', '../path.txt');
        }];
    }

    /**
     * @test
     */
    public function listing_exceptions_are_uniformely_represented(): void
    {
        $filesystem = new Filesystem(
            new AwsS3V3Adapter(
                new S3Client(['region' => 'us-east-1', 'version' => 'latest']),
                'invalid-bucket'
            )
        );
        $items = $filesystem->listContents('', true);

        $this->expectException(UnableToListContents::class);

        iterator_to_array($items); // force the yields
    }

    /**
     * @test
     */
    public function failing_to_create_a_public_url(): void
    {
        $filesystem = new Filesystem(
            new AwsS3V3Adapter(
                new S3Client(['region' => 'us-east-1', 'version' => 'latest']),
                'invalid-bucket'
            )
        );

        $this->expectException(UnableToGeneratePublicUrl::class);

        $filesystem->publicUrl('path.txt');
    }

    /**
     * @test
     */
    public function not_configuring_a_public_url(): void
    {
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());

        $this->expectException(UnableToGeneratePublicUrl::class);

        $filesystem->publicUrl('path.txt');
    }



    /**
     * @test
     */
    public function creating_a_public_url_with_single_prefix(): void
    {
        $filesystem = new Filesystem(
            new InMemoryFilesystemAdapter(),
            ['public_url' => 'https://example.org/public/'],
        );

        $url = $filesystem->publicUrl('path.txt');

        self::assertEquals('https://example.org/public/path.txt', $url);
    }

    /**
     * @test
     */
    public function creating_a_public_url_with_multiple_prefixes(): void
    {
        $filesystem = new Filesystem(
            new InMemoryFilesystemAdapter(),
            ['public_url' => ['https://cdn1', 'https://cdn2']],
        );

        $url1 = $filesystem->publicUrl('path1.txt');
        $url2 = $filesystem->publicUrl('path2.txt');
        $url3 = $filesystem->publicUrl('path1.txt'); // deterministic
        $url4 = $filesystem->publicUrl('/some/path.txt');
        $url5 = $filesystem->publicUrl('some/path.txt'); // deterministic even with leading "/"

        self::assertEquals('https://cdn1/path1.txt', $url1);
        self::assertEquals('https://cdn2/path2.txt', $url2);
        self::assertEquals('https://cdn1/path1.txt', $url3);
        self::assertEquals('https://cdn2/some/path.txt', $url4);
        self::assertEquals('https://cdn2/some/path.txt', $url5);
    }
}
