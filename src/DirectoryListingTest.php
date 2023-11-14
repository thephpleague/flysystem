<?php

declare(strict_types=1);

namespace League\Flysystem;

use Generator;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

/**
 * @group core
 */
class DirectoryListingTest extends TestCase
{
    /**
     * @test
     */
    public function mapping_a_listing(): void
    {
        $numbers = $this->generateIntegers(1, 10);
        $listing = new DirectoryListing($numbers);

        $mappedListing = $listing->map(function (int $i) {
            return $i * 2;
        });
        $mappedNumbers = $mappedListing->toArray();

        $expectedNumbers = [2, 4, 6, 8, 10, 12, 14, 16, 18, 20];
        $this->assertEquals($expectedNumbers, $mappedNumbers);
    }

    /**
     * @test
     */
    public function mapping_a_listing_twice(): void
    {
        $numbers = $this->generateIntegers(1, 10);
        $listing = new DirectoryListing($numbers);

        $mappedListing = $listing->map(function (int $i) {
            return $i * 2;
        });
        $mappedListing = $mappedListing->map(function (int $i) {
            return $i / 2;
        });
        $mappedNumbers = $mappedListing->toArray();

        $expectedNumbers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $this->assertEquals($expectedNumbers, $mappedNumbers);
    }

    /**
     * @test
     */
    public function filter_a_listing(): void
    {
        $numbers = $this->generateIntegers(1, 20);
        $listing = new DirectoryListing($numbers);

        $fileredListing = $listing->filter(function (int $i) {
            return $i % 2 === 0;
        });
        $mappedNumbers = $fileredListing->toArray();

        $expectedNumbers = [2, 4, 6, 8, 10, 12, 14, 16, 18, 20];
        $this->assertEquals($expectedNumbers, $mappedNumbers);
    }

    /**
     * @test
     */
    public function filter_a_listing_twice(): void
    {
        $numbers = $this->generateIntegers(1, 20);
        $listing = new DirectoryListing($numbers);

        $filteredListing = $listing->filter(function (int $i) {
            return $i % 2 === 0;
        });
        $filteredListing = $filteredListing->filter(function (int $i) {
            return $i > 10;
        });
        $mappedNumbers = $filteredListing->toArray();

        $expectedNumbers = [12, 14, 16, 18, 20];
        $this->assertEquals($expectedNumbers, $mappedNumbers);
    }

    /**
     * @test
     */
    public function sorting_a_directory_listing(): void
    {
        $expected = ['a/a/a.txt', 'b/c/a.txt', 'c/b/a.txt', 'c/c/a.txt'];
        $listing = new DirectoryListing([
            new FileAttributes('b/c/a.txt'),
            new FileAttributes('c/c/a.txt'),
            new FileAttributes('c/b/a.txt'),
            new FileAttributes('a/a/a.txt'),
        ]);

        $actual = $listing->sortByPath()
            ->map(function ($i) {
                return $i->path();
            })
            ->toArray();

        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     *
     * @description this ensures that the output of a sorted listing is iterable
     *
     * @see https://github.com/thephpleague/flysystem/issues/1342
     */
    public function iterating_over_storted_output(): void
    {
        $listing = new DirectoryListing([
            new FileAttributes('b/c/a.txt'),
            new FileAttributes('c/c/a.txt'),
            new FileAttributes('c/b/a.txt'),
            new FileAttributes('a/a/a.txt'),
        ]);

        self::expectNotToPerformAssertions();

        iterator_to_array($listing->sortByPath());
    }

    /**
     * @return Generator<int>
     */
    private function generateIntegers(int $min, int $max): Generator
    {
        for ($i = $min; $i <= $max; $i++) {
            yield $i;
        }
    }
}
