<?php

declare(strict_types=1);

namespace League\Flysystem;

use Generator;
use PHPUnit\Framework\TestCase;

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
     * @return Generator<int>
     */
    private function generateIntegers(int $min, int $max): Generator
    {
        for ($i = $min; $i <= $max; $i++) {
            yield $i;
        }
    }
}
