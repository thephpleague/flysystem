<?php

namespace League\Flysystem\Filter;

use League\Flysystem\FilterCriteriaInterface;
use League\Flysystem\FilterFileInfo;

class AndX implements FilterCriteriaInterface
{
    /**
     * @var FilterCriteriaInterface[]
     */
    private $filters = [];

    public function __construct()
    {
        $args = func_get_args();
        $this->guardArgs($args);

        $this->filters = $args;
    }

    /**
     * @inheritdoc
     */
    public function isSatisfiedBy(FilterFileInfo $filterFileInfo)
    {
        foreach ($this->filters as $filter) {
            if ( ! $filter->isSatisfiedBy($filterFileInfo)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Guards if all of arguments passed.
     *
     * @param array $args
     *
     * @throws \InvalidArgumentException
     */
    private function guardArgs(array $args)
    {
        foreach ($args as $arg) {
            if ( ! is_object($arg)) {
                throw new \InvalidArgumentException(
                    'All argument should implements FilterCriteriaInterface. Not an object given.'
                );
            }
            if (is_object($arg) && ! in_array('League\Flysystem\FilterCriteriaInterface', class_implements($arg))) {
                throw new \InvalidArgumentException(
                    sprintf('All argument should implements FilterCriteriaInterface. %s given.', is_object($arg))
                );
            }
        }
    }
}
