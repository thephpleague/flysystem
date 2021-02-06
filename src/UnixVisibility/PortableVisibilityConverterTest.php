<?php

declare(strict_types=1);

namespace League\Flysystem\UnixVisibility;

use League\Flysystem\InvalidVisibilityProvided;
use League\Flysystem\Visibility;
use PHPUnit\Framework\TestCase;

/**
 * @group unix-visibility
 */
class PortableVisibilityConverterTest extends TestCase
{
    /**
     * @test
     */
    public function determining_visibility_for_a_file(): void
    {
        $interpreter = new PortableVisibilityConverter();
        $this->assertEquals(0644, $interpreter->forFile(Visibility::PUBLIC));
        $this->assertEquals(0600, $interpreter->forFile(Visibility::PRIVATE));
    }

    /**
     * @test
     */
    public function determining_an_incorrect_visibility_for_a_file(): void
    {
        $this->expectException(InvalidVisibilityProvided::class);
        $interpreter = new PortableVisibilityConverter();
        $interpreter->forFile('incorrect');
    }

    /**
     * @test
     */
    public function determining_visibility_for_a_directory(): void
    {
        $interpreter = new PortableVisibilityConverter();
        $this->assertEquals(0755, $interpreter->forDirectory(Visibility::PUBLIC));
        $this->assertEquals(0700, $interpreter->forDirectory(Visibility::PRIVATE));
    }

    /**
     * @test
     */
    public function determining_an_incorrect_visibility_for_a_directory(): void
    {
        $this->expectException(InvalidVisibilityProvided::class);
        $interpreter = new PortableVisibilityConverter();
        $interpreter->forDirectory('incorrect');
    }

    /**
     * @test
     */
    public function inversing_for_a_file(): void
    {
        $interpreter = new PortableVisibilityConverter();
        $this->assertEquals(Visibility::PUBLIC, $interpreter->inverseForFile(0644));
        $this->assertEquals(Visibility::PRIVATE, $interpreter->inverseForFile(0600));
        $this->assertEquals(Visibility::PUBLIC, $interpreter->inverseForFile(0404));
    }

    /**
     * @test
     */
    public function inversing_for_a_directory(): void
    {
        $interpreter = new PortableVisibilityConverter();
        $this->assertEquals(Visibility::PUBLIC, $interpreter->inverseForDirectory(0755));
        $this->assertEquals(Visibility::PRIVATE, $interpreter->inverseForDirectory(0700));
        $this->assertEquals(Visibility::PUBLIC, $interpreter->inverseForDirectory(0404));
    }

    /**
     * @test
     */
    public function determining_default_for_directories(): void
    {
        $interpreter = new PortableVisibilityConverter();
        $this->assertEquals(0700, $interpreter->defaultForDirectories());

        $interpreter = new PortableVisibilityConverter(0644, 0600, 0755, 0700, Visibility::PUBLIC);
        $this->assertEquals(0755, $interpreter->defaultForDirectories());
    }

    /**
     * @test
     */
    public function creating_from_array(): void
    {
        $interpreter = PortableVisibilityConverter::fromArray([
            'file' => [
                'public' => 0640,
                'private' => 0604,
            ],
            'dir' => [
                'public' => 0740,
                'private' => 7604,
            ],
        ]);

        $this->assertEquals(0640, $interpreter->forFile(Visibility::PUBLIC));
        $this->assertEquals(0604, $interpreter->forFile(Visibility::PRIVATE));

        $this->assertEquals(0740, $interpreter->forDirectory(Visibility::PUBLIC));
        $this->assertEquals(7604, $interpreter->forDirectory(Visibility::PRIVATE));
    }
}
