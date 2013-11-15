<?php

namespace Flysystem\Cache;

class Memory extends AbstractCache
{
    public function save()
    {
        // There is nothing to save
    }

    public function load()
    {
        // There is nothing to load
    }

    public function flush()
    {
        clearstatcache();

        parent::flush();
    }
}
