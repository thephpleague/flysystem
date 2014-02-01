<?php

namespace League\Flysystem;

use Exception;
use LogicException;

class FileExistsException extends LogicException
{
    protected $path;

    public function __construct($path, $code = 0, Exception $previous = null)
    {
        $this->path = $path;
        parent::__construct('File already exists at path: '.$this->getPath(), $code, $previous);
    }

    public function getPath()
    {
        return $this->path;
    }
}
