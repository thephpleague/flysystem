<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Adapter\Polyfill\PathPrefixTrait;
use League\Flysystem\AdapterInterface;

abstract class AbstractAdapter implements AdapterInterface
{
    use PathPrefixTrait;
}
