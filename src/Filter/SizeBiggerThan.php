<?php

namespace League\Flysystem\Filter;

use League\Flysystem\FilterCriteriaInterface;
use League\Flysystem\FilterFileInfo;

class SizeBiggerThan implements FilterCriteriaInterface
{
    /**
     * @var string
     */
    private $size;

    /**
     * SizeBiggerThan constructor.
     *
     * @param integer $size
     */
    public function __construct($size)
    {
        if ( ! is_integer($size)) {
            throw new \InvalidArgumentException('Size have to be an integer.');
        }

        $this->size = $size;
    }

    /**
     * @inheritdoc
     */
    public function isSatisfiedBy(FilterFileInfo $filterFileInfo)
    {
        return $filterFileInfo->getSize() > $this->size;
    }
}
