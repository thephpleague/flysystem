<?php

namespace League\Flysystem\Stub;

use League\Flysystem\Adapter\Polyfill\StreamedWritingTrait;
use League\Flysystem\Config;

class StreamedWritingStub
{
    use StreamedWritingTrait;

    public function write($path, $contents, Config $config)
    {
        return compact('path', 'contents');
    }

    public function update($path, $contents, Config $config)
    {
        return compact('path', 'contents');
    }
}
