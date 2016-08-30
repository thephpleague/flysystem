<?php

namespace League\Flysystem\Filter;

use League\Flysystem\FilterCriteriaInterface;
use League\Flysystem\FilterFileInfo;

class ExtensionEquals implements FilterCriteriaInterface
{
    /**
     * @var string
     */
    private $extension;

    public function __construct($extension)
    {
        if ( ! is_string($extension)) {
            throw new \InvalidArgumentException('Extenstion have to be a string.');
        }

        $this->extension = $extension;
    }

    /**
     * @inheritdoc
     */
    public function isSatisfiedBy(FilterFileInfo $filterFileInfo)
    {
        return $filterFileInfo->getExtension() === $this->extension;
    }
}
