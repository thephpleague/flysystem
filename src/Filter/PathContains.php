<?php

namespace League\Flysystem\Filter;

use League\Flysystem\FilterCriteriaInterface;
use League\Flysystem\FilterFileInfo;

class PathContains implements FilterCriteriaInterface
{
    /**
     * @var string
     */
    private $pathFragment;

    /**
     * PathContains constructor.
     *
     * @param string $pathFragment
     */
    public function __construct($pathFragment)
    {
        if ( ! is_string($pathFragment)) {
            throw new \InvalidArgumentException('Path fragment have to be a string.');
        }

        $this->pathFragment = $pathFragment;
    }

    /**
     * @inheritdoc
     */
    public function isSatisfiedBy(FilterFileInfo $filterFileInfo)
    {
        return ! (strpos($filterFileInfo->getPath(), $this->pathFragment) === false);
    }
}
