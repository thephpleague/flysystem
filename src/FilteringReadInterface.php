<?php

namespace League\Flysystem;

interface FilteringReadInterface extends ReadInterface
{
    /**
     * List filtered contents of a directory.
     *
     * @param FilterCriteriaInterface $filterCriteria Criteria to filer contents of directory
     * @param string                  $directory      The directory to list.
     * @param bool                    $recursive      Whether to list recursively.
     *
     * @throws UnsupportedFilterException
     *
     * @return array A list of file metadata.
     */
    public function listFilteredContents(FilterCriteriaInterface $filterCriteria, $directory = '', $recursive = false);
}
