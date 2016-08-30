<?php

namespace League\Flysystem\Filter;

use League\Flysystem\FilterCriteriaInterface;
use League\Flysystem\FilterFileInfo;

class NotX implements FilterCriteriaInterface
{
    /**
     * @var FilterCriteriaInterface[]
     */
    private $baseFilter;

    /**
     * NotX constructor.
     *
     * @param FilterCriteriaInterface $baseFilterCriteria
     */
    public function __construct(FilterCriteriaInterface $baseFilterCriteria)
    {
        $this->baseFilter = $baseFilterCriteria;
    }

    /**
     * @inheritdoc
     */
    public function isSatisfiedBy(FilterFileInfo $filterFileInfo)
    {
        return ! $this->baseFilter->isSatisfiedBy($filterFileInfo);
    }
}
