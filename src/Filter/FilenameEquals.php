<?php

namespace League\Flysystem\Filter;

use League\Flysystem\FilterCriteriaInterface;
use League\Flysystem\FilterFileInfo;

class FilenameEquals implements FilterCriteriaInterface
{
    /**
     * @var string
     */
    private $fileName;

    /**
     * FilenameEquals constructor.
     *
     * @param string $fileName
     */
    public function __construct($fileName)
    {
        if ( ! is_string($fileName)) {
            throw new \InvalidArgumentException('File name have to be a string.');
        }

        $this->fileName = $fileName;
    }

    /**
     * @inheritdoc
     */
    public function isSatisfiedBy(FilterFileInfo $filterFileInfo)
    {
        return $filterFileInfo->getFilename() === $this->fileName;
    }
}
