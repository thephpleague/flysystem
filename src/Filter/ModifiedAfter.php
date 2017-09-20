<?php

namespace League\Flysystem\Filter;

use League\Flysystem\FilterCriteriaInterface;
use League\Flysystem\FilterFileInfo;

class ModifiedAfter implements FilterCriteriaInterface
{
    /**
     * @var \DateTime
     */
    private $dateTime;

    /**
     * ModifiedAfter constructor.
     *
     * @param \DateTime $dateTime
     */
    public function __construct(\DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * @inheritdoc
     */
    public function isSatisfiedBy(FilterFileInfo $filterFileInfo)
    {
        return $filterFileInfo->getTimestamp() > date_timestamp_get($this->dateTime);
    }
}
