<?php

namespace League\Flysystem;

use RuntimeException;
use SplFileInfo;

class NotSupportedException extends RuntimeException
{
    /**
     * Create a new exception for a link.
     *
     * @param SplFileInfo $file
     * @return static
     */
    public static function forLink(SplFileInfo $file)
    {
        $message = 'Links are not supported, encountered link at ';

        return new static($message.$file->getPathname());
    }
}
