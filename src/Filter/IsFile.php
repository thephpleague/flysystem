<?php

namespace League\Flysystem\Filter;

use League\Flysystem\FilterCriteriaInterface;
use League\Flysystem\FilterFileInfo;

class IsFile implements FilterCriteriaInterface
{
    /**
     * @inheritdoc
     */
    public function isSatisfiedBy(FilterFileInfo $filterFileInfo)
    {
        return $filterFileInfo->getType() === FilterFileInfo::TYPE_FILE;
    }
}
