<?php

namespace Flysystem\Adapter;

use LogicException;
use Flysystem\AdapterInterface;

abstract class AbstractAdapter implements AdapterInterface
{
    public function getVisibility($path)
    {
        throw new LogicException(get_class($this).' does not support visibility settings.');
    }

    public function setVisibility($path, $visibility)
    {
        throw new LogicException(get_class($this).' does not support visibility settings.');
    }
}
