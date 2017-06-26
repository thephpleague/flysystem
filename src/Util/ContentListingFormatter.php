<?php

namespace League\Flysystem\Util;

use League\Flysystem\Util;

class ContentListingFormatter
{
    /**
     * @var string
     */
    protected $directory;

    /**
     * @var bool
     */
    protected $recursive;

    /**
     * @param string $directory
     * @param bool   $recursive
     */
    public function __construct($directory, $recursive)
    {
        $this->directory = $directory;
        $this->recursive = $recursive;
    }

    /**
     * Format contents listing.
     *
     * @param array $listing
     *
     * @return array
     */
    public function formatListing(array $listing)
    {
        $listing = array_values(
            array_map(
                [$this, 'addPathInfo'],
                array_filter($listing, [$this, 'isEntryOutOfScope'])
            )
        );

        return $this->sortListing($listing);
    }

    protected function addPathInfo(array $entry)
    {
        return $entry + Util::pathinfo($entry['path']);
    }

    /**
     * Determine if the entry is out of scope.
     *
     * @param array $entry
     *
     * @return bool
     */
    protected function isEntryOutOfScope(array $entry)
    {
        if (empty($entry['path']) && $entry['path'] !== '0') {
            return false;
        }

        if ($this->recursive) {
            return $this->residesInDirectory($entry);
        }

        return $this->isDirectChild($entry);
    }

    /**
     * Check if the entry resides within the parent directory.
     *
     * @param $entry
     *
     * @return bool
     */
    protected function residesInDirectory(array $entry)
    {
        if ($this->directory === '') {
            return true;
        }

        return strpos($entry['path'], $this->directory . '/') === 0;
    }

    /**
     * Check if the entry is a direct child of the directory.
     *
     * @param $entry
     *
     * @return bool
     */
    protected function isDirectChild(array $entry)
    {
        return Util::dirname($entry['path']) === $this->directory;
    }

    /**
     * @param array $listing
     *
     * @return array
     */
    protected function sortListing(array $listing)
    {
        usort(
            $listing,
            function ($a, $b) {
                return strcasecmp($a['path'], $b['path']);
            }
        );

        return $listing;
    }
}
