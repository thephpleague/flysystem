<?php

namespace League\Flysystem\Filter;

use League\Flysystem\FilterCriteriaInterface;
use League\Flysystem\FilterFileInfo;

class IsPublic implements FilterCriteriaInterface
{
    /**
     * @inheritdoc
     */
    public function isSatisfiedBy(FilterFileInfo $filterFileInfo)
    {
        return $filterFileInfo->getVisibility() === FilterFileInfo::VISIBILITY_PUBLIC;
    }
}
