<?php

namespace League\Flysystem\InMemory;

class StaticInMemoryAdapterRegistry
{
    /** @var array<string, InMemoryFilesystemAdapter> */
    private static array $filesystems = [];

    public static function get(string $name = 'default'): InMemoryFilesystemAdapter
    {
        return static::$filesystems[$name] ??= new InMemoryFilesystemAdapter();
    }

    public static function deleteAllFilesystems(): void
    {
        self::$filesystems = [];
    }
}
