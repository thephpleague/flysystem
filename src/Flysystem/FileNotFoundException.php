<?php

namespace Flysystem;

use Exception;
use LogicException;

class FileNotFoundException extends LogicException
{
    protected $path;

    public function __construct($path, $code = 0, Exception $previous = null)
    {
        $this->path = $path;
        parent::__construct('File not found at path: '.$path, $code, $previous);
    }

    public function getPath()
    {
        return $path;
    }
}
