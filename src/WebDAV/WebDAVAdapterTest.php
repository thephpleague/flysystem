<?php

declare(strict_types=1);

namespace League\Flysystem\WebDAV;

use League\Flysystem\FileAttributes;
use PHPUnit\Framework\TestCase;
use Sabre\DAV\Client;

class WebDAVAdapterTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider listingPaths
     */
    public function listing_preserves_encoded_path_characters(string $url, string $expectedPath): void
    {
        $client = $this->createMock(Client::class);
        $client->expects(self::once())
            ->method('propFind')
            ->with('prefix/', WebDAVAdapter::FIND_PROPERTIES, 1)
            ->willReturn([
                '/prefix/' => ['{DAV:}iscollection' => '1'],
                $url => ['{DAV:}getcontentlength' => '12'],
            ]);

        $adapter = new WebDAVAdapter($client, 'prefix');
        $listing = iterator_to_array($adapter->listContents('', false));

        self::assertCount(1, $listing);
        self::assertInstanceOf(FileAttributes::class, $listing[0]);
        self::assertSame($expectedPath, $listing[0]->path());
        self::assertSame(12, $listing[0]->fileSize());
    }

    public static function listingPaths(): iterable
    {
        yield 'hash in relative URL' => ['/prefix/file%23example.txt', 'file#example.txt'];
        yield 'hash in absolute URL' => ['https://example.com/prefix/file%23example.txt', 'file#example.txt'];
        yield 'question mark in relative URL' => ['/prefix/file%3Fexample.txt', 'file?example.txt'];
        yield 'question mark in absolute URL' => ['https://example.com/prefix/file%3Fexample.txt', 'file?example.txt'];
        yield 'encoded percent sign' => ['/prefix/file%2523example.txt', 'file%23example.txt'];
        yield 'space and Unicode' => ['/prefix/caf%C3%A9%20menu.txt', 'café menu.txt'];
        yield 'URL query and fragment' => ['https://example.com/prefix/file%23example.txt?download=1#section', 'file#example.txt'];
    }
}
