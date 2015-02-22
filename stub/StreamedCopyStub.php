<?php

namespace League\Flysystem\Stub;

use League\Flysystem\Adapter\Polyfill\StreamedCopyTrait;
use League\Flysystem\Config;

class StreamedCopyStub
{
    use StreamedCopyTrait;

    private $readResponse;

    private $writeResponse;

    public function __construct($readResponse, $writeResponse = null)
    {
        $this->readResponse = $readResponse;
        $this->writeResponse = $writeResponse;
    }

    /**
     * @param string $path
     */
    public function readStream($path)
    {
        return $this->readResponse;
    }

    /**
     * @param string $path
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->writeResponse;
    }
}
