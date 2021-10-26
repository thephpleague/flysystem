<?php

declare(strict_types=1);

namespace League\Flysystem;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function fopen;

/**
 * @group core
 */
class PrefixedFilesystemTest extends TestCase
{
    /**
     * @var FilesystemOperator & MockObject
     */
    private $decorated;

    /**
     * @var PrefixedFilesystem
     */
    private $filesystem;

    /**
     * @before
     */
    public function setupFilesystem(): void
    {
        $this->decorated = $this->createMock(FilesystemOperator::class);
        $this->filesystem = new PrefixedFilesystem($this->decorated, 'this_is_a_prefix');
    }

    public function testFileExists(): void
    {
        $this->decorated
            ->expects(self::once())
            ->method('fileExists')
            ->with('this_is_a_prefix/file.txt')
            ->willReturn(true);

        self::assertTrue($this->filesystem->fileExists('file.txt'));
    }

    public function testRead(): void
    {
        $this->decorated
            ->expects(self::once())
            ->method('read')
            ->with('this_is_a_prefix/file.txt')
            ->willReturn('content');

        self::assertSame('content', $this->filesystem->read('file.txt'));
    }

    public function testReadStream(): void
    {
        $stream = fopen('php://temp', 'r');
        $this->decorated
            ->expects(self::once())
            ->method('readStream')
            ->with('this_is_a_prefix/file.txt')
            ->willReturn($stream);

        self::assertSame($stream, $this->filesystem->readStream('file.txt'));
    }

    public function testListContents(): void
    {
        $directoryListing = new DirectoryListing([]);
        $this->decorated
            ->expects(self::once())
            ->method('listContents')
            ->with('this_is_a_prefix/dir', FilesystemReader::LIST_DEEP)
            ->willReturn($directoryListing);

        self::assertSame($directoryListing, $this->filesystem->listContents('dir', FilesystemReader::LIST_DEEP));
    }

    public function testLastModified(): void
    {
        $this->decorated
            ->expects(self::once())
            ->method('lastModified')
            ->with('this_is_a_prefix/file.txt')
            ->willReturn(1234);

        self::assertSame(1234, $this->filesystem->lastModified('file.txt'));
    }

    public function testFileSize(): void
    {
        $this->decorated
            ->expects(self::once())
            ->method('fileSize')
            ->with('this_is_a_prefix/file.txt')
            ->willReturn(1234);

        self::assertSame(1234, $this->filesystem->fileSize('file.txt'));
    }

    public function testMimeType(): void
    {
        $this->decorated
            ->expects(self::once())
            ->method('mimeType')
            ->with('this_is_a_prefix/file.txt')
            ->willReturn('text');

        self::assertSame('text', $this->filesystem->mimeType('file.txt'));
    }

    public function testVisibility(): void
    {
        $this->decorated
            ->expects(self::once())
            ->method('visibility')
            ->with('this_is_a_prefix/file.txt')
            ->willReturn(Visibility::PRIVATE);

        self::assertSame(Visibility::PRIVATE, $this->filesystem->visibility('file.txt'));
    }

    public function testWrite(): void
    {
        $this->decorated
            ->expects(self::once())
            ->method('write')
            ->with('this_is_a_prefix/file.txt', 'lorem ipsum', ['config' => true]);

        $this->filesystem->write('file.txt', 'lorem ipsum', ['config' => true]);
    }

    public function testWriteStream(): void
    {
        $stream = fopen('php://temp', 'r');
        $this->decorated
            ->expects(self::once())
            ->method('writeStream')
            ->with('this_is_a_prefix/file.txt', $stream, ['config' => true]);

        $this->filesystem->writeStream('file.txt', $stream, ['config' => true]);
    }

    public function testSetVisibility(): void
    {
        $this->decorated
            ->expects(self::once())
            ->method('setVisibility')
            ->with('this_is_a_prefix/file.txt', Visibility::PRIVATE);

        $this->filesystem->setVisibility('file.txt', Visibility::PRIVATE);
    }

    public function testDelete(): void
    {
        $this->decorated
            ->expects(self::once())
            ->method('delete')
            ->with('this_is_a_prefix/file.txt');

        $this->filesystem->delete('file.txt');
    }

    public function testDeleteDirectory(): void
    {
        $this->decorated
            ->expects(self::once())
            ->method('deleteDirectory')
            ->with('this_is_a_prefix/dir');

        $this->filesystem->deleteDirectory('dir');
    }

    public function testCreateDirectory(): void
    {
        $this->decorated
            ->expects(self::once())
            ->method('createDirectory')
            ->with('this_is_a_prefix/dir', ['config' => true]);

        $this->filesystem->createDirectory('dir', ['config' => true]);
    }

    public function testMove(): void
    {
        $this->decorated
            ->expects(self::once())
            ->method('move')
            ->with('this_is_a_prefix/file1.txt', 'this_is_a_prefix/file2.txt', ['config' => true]);

        $this->filesystem->move('file1.txt', 'file2.txt', ['config' => true]);
    }

    public function testCopy(): void
    {
        $this->decorated
            ->expects(self::once())
            ->method('copy')
            ->with('this_is_a_prefix/file1.txt', 'this_is_a_prefix/file2.txt', ['config' => true]);

        $this->filesystem->copy('file1.txt', 'file2.txt', ['config' => true]);
    }
}
