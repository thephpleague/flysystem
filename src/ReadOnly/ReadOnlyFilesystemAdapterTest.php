<?php

namespace League\Flysystem\ReadOnly;

use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToGeneratePublicUrl;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use PHPUnit\Framework\TestCase;

use function ltrim;

class ReadOnlyFilesystemAdapterTest extends TestCase
{
    /**
     * @test
     */
    public function can_perform_read_operations(): void
    {
        $adapter = $this->realAdapter();
        $adapter->write('foo/bar.txt', 'content', new Config());

        $adapter = new ReadOnlyFilesystemAdapter($adapter);

        $this->assertTrue($adapter->fileExists('foo/bar.txt'));
        $this->assertTrue($adapter->directoryExists('foo'));
        $this->assertSame('content', $adapter->read('foo/bar.txt'));
        $this->assertSame('content', \stream_get_contents($adapter->readStream('foo/bar.txt')));
        $this->assertInstanceOf(FileAttributes::class, $adapter->visibility('foo/bar.txt'));
        $this->assertInstanceOf(FileAttributes::class, $adapter->mimeType('foo/bar.txt'));
        $this->assertInstanceOf(FileAttributes::class, $adapter->lastModified('foo/bar.txt'));
        $this->assertInstanceOf(FileAttributes::class, $adapter->fileSize('foo/bar.txt'));
        $this->assertCount(1, iterator_to_array($adapter->listContents('foo', true)));
    }

    /**
     * @test
     */
    public function cannot_write_stream(): void
    {
        $adapter = new ReadOnlyFilesystemAdapter($this->realAdapter());

        $this->expectException(UnableToWriteFile::class);

        // @phpstan-ignore-next-line
        $adapter->writeStream('foo', 'content', new Config());
    }

    /**
     * @test
     */
    public function cannot_write(): void
    {
        $adapter = new ReadOnlyFilesystemAdapter($this->realAdapter());

        $this->expectException(UnableToWriteFile::class);

        $adapter->write('foo', 'content', new Config());
    }

    /**
     * @test
     */
    public function cannot_delete_file(): void
    {
        $adapter = $this->realAdapter();
        $adapter->write('foo', 'content', new Config());

        $adapter = new ReadOnlyFilesystemAdapter($adapter);

        $this->expectException(UnableToDeleteFile::class);

        $adapter->delete('foo');
    }

    /**
     * @test
     */
    public function cannot_delete_directory(): void
    {
        $adapter = $this->realAdapter();
        $adapter->createDirectory('foo', new Config());

        $adapter = new ReadOnlyFilesystemAdapter($adapter);

        $this->expectException(UnableToDeleteDirectory::class);

        $adapter->deleteDirectory('foo');
    }

    /**
     * @test
     */
    public function cannot_create_directory(): void
    {
        $adapter = new ReadOnlyFilesystemAdapter($this->realAdapter());

        $this->expectException(UnableToCreateDirectory::class);

        $adapter->createDirectory('foo', new Config());
    }

    /**
     * @test
     */
    public function cannot_set_visibility(): void
    {
        $adapter = $this->realAdapter();
        $adapter->write('foo', 'content', new Config());

        $adapter = new ReadOnlyFilesystemAdapter($adapter);

        $this->expectException(UnableToSetVisibility::class);

        $adapter->setVisibility('foo', 'private');
    }

    /**
     * @test
     */
    public function cannot_move(): void
    {
        $adapter = $this->realAdapter();
        $adapter->write('foo', 'content', new Config());

        $adapter = new ReadOnlyFilesystemAdapter($adapter);

        $this->expectException(UnableToMoveFile::class);

        $adapter->move('foo', 'bar', new Config());
    }

    /**
     * @test
     */
    public function cannot_copy(): void
    {
        $adapter = $this->realAdapter();
        $adapter->write('foo', 'content', new Config());

        $adapter = new ReadOnlyFilesystemAdapter($adapter);

        $this->expectException(UnableToCopyFile::class);

        $adapter->copy('foo', 'bar', new Config());
    }

    /**
     * @test
     */
    public function generating_a_public_url(): void
    {
        $adapter = new class() extends InMemoryFilesystemAdapter implements PublicUrlGenerator {
            public function publicUrl(string $path, Config $config): string
            {
                return 'memory://' . ltrim($path, '/');
            }
        };
        $readOnlyAdapter = new ReadOnlyFilesystemAdapter($adapter);

        $url = $readOnlyAdapter->publicUrl('/path.txt', new Config());

        self::assertEquals('memory://path.txt', $url);
    }

    /**
     * @test
     */
    public function failing_to_generate_a_public_url(): void
    {
        $adapter = new ReadOnlyFilesystemAdapter(new InMemoryFilesystemAdapter());

        $this->expectException(UnableToGeneratePublicUrl::class);

        $adapter->publicUrl('/path.txt', new Config());
    }

    private function realAdapter(): InMemoryFilesystemAdapter
    {
        return new InMemoryFilesystemAdapter();
    }
}
