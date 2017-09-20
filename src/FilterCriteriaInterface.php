<?php

namespace League\Flysystem;

interface FilterCriteriaInterface
{
    /**
     * Detrrmines that file info satisfying criteria or not.
     *
     * @param FilterFileInfo $filterFileInfo
     *
     * @return bool
     */
    public function isSatisfiedBy(FilterFileInfo $filterFileInfo);
}
