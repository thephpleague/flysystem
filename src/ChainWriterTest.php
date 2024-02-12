<?php

namespace League\Flysystem;

use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChainWriterTest extends TestCase
{
    /**
     * @var ChainWriter
     */
    private $chain;

    /**
     * @var MockObject[]
     */
    private $filesystems;

    /**
     * @before
     * @throws MockException
     */
    public function setupFilesystem(): void
    {
        $this->filesystems = [];
        $this->filesystems[] = $this->createMock(Filesystem::class);
        $this->filesystems[] = $this->createMock(Filesystem::class);
        $this->filesystems[] = $this->createMock(Filesystem::class);
        $this->chain = new ChainWriter($this->filesystems);
    }

    /**
     * @test
     * @throws MockException
     */
    public function add_and_get_a_filesystems(): void
    {
        $expectedCount = count($this->filesystems);
        $actualCount = count($this->chain->getFilesystems());
        $this->assertSame($expectedCount, $actualCount);
        $this->chain->addFilesystem($this->createMock(FilesystemWriter::class));
        $expectedCount++;
        $actualCount = count($this->chain->getFilesystems());
        $this->assertSame($expectedCount, $actualCount);

        foreach ($this->chain->getFilesystems() as $filesystem) {
            $this->assertInstanceOf(FilesystemWriter::class, $filesystem);
        }
    }

    /**
     * @test
     */
    public function write_to_file(): void
    {
        $this->assertCallMethodOnAllFilesystems('write', ['/file', 'text', []]);
    }

    /**
     * @test
     */
    public function writing_a_stream(): void
    {
        $this->assertCallMethodOnAllFilesystems('writeStream', ['/file', 'text', []]);
    }

    /**
     * @test
     */
    public function setting_visibility(): void
    {
        $this->assertCallMethodOnAllFilesystems('setVisibility', ['/file', Visibility::PUBLIC]);
    }

    /**
     * @test
     */
    public function deleting_a_file(): void
    {
        $this->assertCallMethodOnAllFilesystems('delete', ['/file']);
    }

    /**
     * @test
     */
    public function deleting_a_directory(): void
    {
        $this->assertCallMethodOnAllFilesystems('deleteDirectory', ['/directory']);
    }

    /**
     * @test
     */
    public function creating_a_directory(): void
    {
        $this->assertCallMethodOnAllFilesystems('createDirectory', ['/directory', []]);
    }

    /**
     * @test
     */
    public function copying_files(): void
    {
        $this->assertCallMethodOnAllFilesystems('copy', ['/file1', '/file2', []]);
    }

    private function assertCallMethodOnAllFilesystems(string $methodName, array $args = []): void
    {
        $processed = [];

        foreach ($this->filesystems as $i => $filesystem) {
            $filesystem->method($methodName)->willReturnCallback(function () use (&$processed, $i) {
                $processed[] = [$i, func_get_args()];
            });
        }

        call_user_func_array([$this->chain, $methodName], $args);

        $filesystemIndexes = array_column($processed, 0);
        $uniqueIndexes = array_unique($filesystemIndexes);
        $this->assertSame(count($this->filesystems), count($uniqueIndexes));

        foreach ($processed as $item) {
            list($index, $actualArgs) = $item;
            $this->assertSame($args, $actualArgs);
        }
    }
}
