<?php

declare(strict_types=1);

namespace League\Flysystem;

use Generator;
use IteratorAggregate;

/**
 * @template T
 */
class DirectoryListing implements IteratorAggregate
{
    /**
     * @var Generator<T>
     */
    private $listing;

    /**
     * @param Generator<T> $listing
     */
    public function __construct(Generator $listing)
    {
        $this->listing = $listing;
    }

    public function filter(callable $filter): DirectoryListing
    {
        $generator = (static function (Generator $listing) use ($filter): Generator {
            foreach ($listing as $item) {
                if ($filter($item)) {
                    yield $item;
                }
            }
        })($this->listing);

        return new DirectoryListing($generator);
    }

    public function map(callable $mapper): DirectoryListing
    {
        $generator = (static function (Generator $listing) use ($mapper): Generator {
            foreach ($listing as $item) {
                yield $mapper($item);
            }
        })($this->listing);

        return new DirectoryListing($generator);
    }

    /**
     * @return Generator<T>
     */
    public function getIterator(): Generator
    {
        return $this->listing;
    }

    /**
     * @return T[]
     */
    public function toArray(): array
    {
        return iterator_to_array($this->listing, false);
    }

}
