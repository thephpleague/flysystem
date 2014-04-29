<?php

namespace League\Flysystem;

use Exception as BaseException;

class FileExistsException extends Exception
{
    protected $path;

    public function __construct($path, $code = 0, BaseException $previous = null)
    {
        $this->path = $path;

        parent::__construct('File already exists at path: '.$this->getPath(), $code, $previous);
    }

    public function getPath()
    {
        return $this->path;
    }
}
