<?php

declare(strict_types=1);

namespace League\Flysystem\AzureBlobStorage;

use PHPUnit\Framework\TestCase;

class FirstDirectoryAsContainerPathResolverTest extends TestCase
{
    public function resolvedData()
    {
        yield 'backup directory' => ['filename.jpg', 'backup-container', 'filename.jpg'];
        yield 'single directory' => ['container1/filename.jpg', 'container1', 'filename.jpg'];
        yield 'two directories' => ['container1/directory1/filename.jpg', 'container1', 'directory1/filename.jpg'];
    }

    /**
     * @dataProvider resolvedData
     */
    public function testResolved(string $path, string $expectedContainer, string $expectedPath)
    {
        $resolver = new FirstDirectoryAsContainerPathResolver('backup-container');
        $resolved = $resolver->resolve($path);

        self::assertEquals($expectedContainer, $resolved->getContainer());
        self::assertEquals($expectedPath, $resolved->getPath());
    }
}
