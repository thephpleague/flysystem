<?php

namespace League\Flysystem\InMemory;

use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;

class StaticInMemoryAdapterRegistryTest extends InMemoryFilesystemAdapterTest
{
    /**
     * @test
     */
    public function using_different_name_to_segment_adapters(): void
    {
        $first = StaticInMemoryAdapterRegistry::get();
        $second = StaticInMemoryAdapterRegistry::get('second');

        $first->write('foo.txt', 'foo', new Config());
        $second->write('bar.txt', 'bar', new Config());

        $this->assertTrue($first->fileExists('foo.txt'));
        $this->assertFalse($first->fileExists('bar.txt'));
        $this->assertTrue($second->fileExists('bar.txt'));
        $this->assertFalse($second->fileExists('foo.txt'));
    }

    /**
     * @test
     */
    public function files_persist_between_instances(): void
    {
        $first = StaticInMemoryAdapterRegistry::get();
        $second = StaticInMemoryAdapterRegistry::get('second');

        $first->write('foo.txt', 'foo', new Config());
        $second->write('bar.txt', 'bar', new Config());

        $this->assertTrue($first->fileExists('foo.txt'));
        $this->assertTrue($second->fileExists('bar.txt'));

        $first = StaticInMemoryAdapterRegistry::get();
        $second = StaticInMemoryAdapterRegistry::get('second');

        $this->assertTrue($first->fileExists('foo.txt'));
        $this->assertTrue($second->fileExists('bar.txt'));
    }

    protected function tearDown(): void
    {
        StaticInMemoryAdapterRegistry::deleteAllFilesystems();
    }

    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        return StaticInMemoryAdapterRegistry::get();
    }
}
