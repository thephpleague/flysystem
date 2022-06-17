<?php

declare(strict_types=1);

namespace League\Flysystem\Replicate;

use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\Visibility;
use LogicException;

/**
 * @group replicate
 */
class ReplicateFilesystemAdapterTest extends FilesystemAdapterTestCase
{
    /**
     * @var FilesystemAdapter
     */
    protected static $source;
    /**
     * @var FilesystemAdapter
     */
    protected static $replica;

    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        static::$source = new InMemoryFilesystemAdapter();
        static::$replica = new InMemoryFilesystemAdapter();

        return new ReplicateFilesystemAdapter(static::$source, static::$replica);
    }

    /**
     * @return ReplicateFilesystemAdapter
     *
     * @throws LogicException
     */
    public function adapter(): FilesystemAdapter
    {
        $adapter = parent::adapter();

        if ( ! $adapter instanceof ReplicateFilesystemAdapter) {
            throw new LogicException();
        }

        return $adapter;
    }

    /**
     * @return array<array{0: string, 1: array<mixed>, 2: bool, 3?: mixed}>
     */
    public function callProvider()
    {
        return [
            ['fileExists', ['path'], false, true],
            ['write', ['path', 'content', new Config()], true],
            ['writeStream', ['path', stream_with_contents('content'), new Config()], true],
            ['read', ['path'], false, 'content'],
            ['readStream', ['path'], false, stream_with_contents('content')],
            ['delete', ['path'], false],
            ['deleteDirectory', ['path'], true],
            ['createDirectory', ['path', new Config()], true],
            ['setVisibility', ['path', 'visibility'], true],
            ['visibility', ['path'], false, new FileAttributes('path', null, Visibility::PRIVATE)],
            ['mimeType', ['path'], false, new FileAttributes('path', null, null, null, 'text/plain')],
            ['lastModified', ['path'], false, new FileAttributes('path', null, null, time())],
            ['fileSize', ['path'], false, new FileAttributes('path', 7)],
            ['listContents', ['path', false], false, [new DirectoryAttributes('path/d'),new FileAttributes('path/1'),new FileAttributes('path/2')]],
            ['move', ['path/old', 'path/new', new Config()], true],
            ['copy', ['path/old', 'path/new', new Config()], true],
        ];
    }

    /**
     * @dataProvider callProvider
     * @test
     *
     * @param array<mixed> $arguments
     * @param mixed $return
     */
    public function method_delegation(string $method, array $arguments, bool $useReplica, $return = null): void
    {
        $source = $this->createMock(FilesystemAdapter::class);
        $replica = $this->createMock(FilesystemAdapter::class);
        $adapter = new ReplicateFilesystemAdapter($source, $replica);
        $this->useAdapter($adapter);

        $sourceInvocation = $source->expects($this->once())
                                   ->method($method)
                                   ->with(...$arguments);
        if (isset($return)) {
            $sourceInvocation->willReturn($return);
        }

        $replicaInvocation = $replica->expects($useReplica === true ? $this->once() : $this->never())
                                     ->method($method)
                                     ->with(...$arguments);
        if (isset($return)) {
            $replicaInvocation->willReturn($return);
        }

        $this->assertSame($return, \call_user_func_array([$this->adapter(), $method], $arguments));
    }

    /**
     * @test
     */
    public function getting_source_adapter(): void
    {
        $this->assertSame(static::$source, $this->adapter()->getSourceAdapter());
    }

    /**
     * @test
     */
    public function getting_replica_adapter(): void
    {
        $this->assertSame(static::$replica, $this->adapter()->getReplicaAdapter());
    }

    /**
     * @test
     */
    public function deleting_file_from_replica(): void
    {
        $source = new InMemoryFilesystemAdapter();
        $replica = $this->createMock(FilesystemAdapter::class);
        $adapter = new ReplicateFilesystemAdapter($source, $replica);
        $this->useAdapter($adapter);

        $replica->expects($this->once())
                ->method('fileExists')
                ->willReturn(true);
        $replica->expects($this->once())
                ->method('delete')
                ->with('path');

        $this->adapter()->delete('path');
    }

    /**
     * @test
     */
    public function writing_non_seekable_stream(): void
    {
        stream_wrapper_register('test', NonSeekableStream::class);

        $source = new InMemoryFilesystemAdapter();
        $replica = $this->createMock(FilesystemAdapter::class);
        $adapter = new ReplicateFilesystemAdapter($source, $replica);
        $this->useAdapter($adapter);

        $content = fopen('test://content', 'r+');

        $replica->expects($this->once())
                ->method('writeStream')
                ->with('path', $this->logicalNot($this->equalTo($content)));

        $this->adapter()->writeStream('path', $content, new Config());

        stream_wrapper_unregister('test');
    }
}

class NonSeekableStream
{
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        return true;
    }

    public function stream_read(int $count): string
    {
        return '';
    }

    public function stream_eof(): bool
    {
        return true;
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        return false;
    }

    public function stream_stat(): array
    {
        return [];
    }
}
