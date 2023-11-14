<?php

namespace League\Flysystem\UrlGeneration;

use League\Flysystem\Config;
use League\Flysystem\UnableToGeneratePublicUrl;
use PHPUnit\Framework\TestCase;

final class ChainedPublicUrlGeneratorTest extends TestCase
{
    /**
     * @test
     */
    public function can_generate_url_for_supported_generator(): void
    {
        $generator = new ChainedPublicUrlGenerator([
            new class() implements PublicUrlGenerator {
                public function publicUrl(string $path, Config $config): string
                {
                    throw new UnableToGeneratePublicUrl('not supported', $path);
                }
            },
            new PrefixPublicUrlGenerator('/prefix'),
        ]);

        $this->assertSame('/prefix/some/path', $generator->publicUrl('some/path', new Config()));
    }

    /**
     * @test
     */
    public function no_supported_generator_found_throws_exception(): void
    {
        $generator = new ChainedPublicUrlGenerator([
            new class() implements PublicUrlGenerator {
                public function publicUrl(string $path, Config $config): string
                {
                    throw new UnableToGeneratePublicUrl('not supported', $path);
                }
            },
        ]);

        $this->expectException(UnableToGeneratePublicUrl::class);
        $this->expectExceptionMessage('Unable to generate public url for some/path: No supported public url generator found.');

        $generator->publicUrl('some/path', new Config());
    }
}
