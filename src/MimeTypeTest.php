<?php

declare(strict_types=1);

namespace League\Flysystem;


use PHPUnit\Framework\TestCase;

class MimeTypeTest extends TestCase
{
    /**
     * @test
     */
    public function falling_back_to_extension_detection(): void
    {
        $mimeType = MimeType::detectMimeType('path.svg', '');
        $this->assertEquals('image/svg+xml', $mimeType);
    }

    /**
     * @test
     */
    public function detecting_by_contents(): void
    {
        /** @var string $contents */
        $contents = file_get_contents(__DIR__.'/../test_files/flysystem.svg');

        $mimeType = MimeType::detectMimeType('nope.txt', $contents);

        $this->assertEquals('image/svg', $mimeType);
    }

    /**
     * @test
     */
    public function testing_with_non_string_body(): void
    {
        $mimeType = MimeType::detectMimeType('path.svg', null);
        $this->assertEquals('image/svg+xml', $mimeType);
    }
}
