<?php

namespace League\Flysystem\Filter;

use League\Flysystem\FilterCriteriaInterface;
use League\Flysystem\FilterFileInfo;

class FilenameStartsWith implements FilterCriteriaInterface
{
    /**
     * @var string
     */
    private $fileNameStart;

    /**
     * FilenameStartsWith constructor.
     *
     * @param string $fileNameStart
     */
    public function __construct($fileNameStart)
    {
        if ( ! is_string($fileNameStart)) {
            throw new \InvalidArgumentException('File name have to be a string.');
        }

        $this->fileNameStart = $fileNameStart;
    }

    /**
     * @inheritdoc
     */
    public function isSatisfiedBy(FilterFileInfo $filterFileInfo)
    {
        return strpos($filterFileInfo->getFilename(), $this->fileNameStart) === 0;
    }
}
