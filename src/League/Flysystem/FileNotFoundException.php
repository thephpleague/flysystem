<?php

namespace League\Flysystem;

use Exception;
use LogicException;

class FileNotFoundException extends LogicException
{
    /**
     * @var  string  $path
     */
    protected $path;

    /**
     * Constructor
     *
     * @param  string     $path
     * @param  int        $code
     * @param  Exception  $previous
     */
    public function __construct($path, $code = 0, Exception $previous = null)
    {
        $this->path = $path;
        parent::__construct('File not found at path: '.$this->getPath(), $code, $previous);
    }

    /**
     * Get the path which was not found
     *
     * @return  string
     */
    public function getPath()
    {
        return $this->path;
    }
}
