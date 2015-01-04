<?php

namespace League\Flysystem\Stub;

use League\Flysystem\Adapter\Polyfill\StreamedReadingTrait;

class StreamedReadingStub
{
    use StreamedReadingTrait;

    public function read($path)
    {
        if ($path === 'true.ext') {
            return ['contents' => $path];
        }

        return false;
    }
}
