<?php

declare(strict_types=1);

namespace League\Flysystem;

use Generator;
use IteratorAggregate;

class DirectoryListing implements IteratorAggregate
{
    /**
     * @var Generator
     */
    private $listing;

    public function __construct(Generator $listing)
    {
        $this->listing = $listing;
    }

    /**
     * @return Generator<StorageAttributes>
     */
    public function getIterator(): Generator
    {
        return $this->listing;
    }

    /**
     * @return StorageAttributes[]
     */
    public function toArray(): array
    {
        return iterator_to_array($this->listing);
    }

}
