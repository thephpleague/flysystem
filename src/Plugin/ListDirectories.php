<?php

namespace League\Flysystem\Plugin;

class ListDirectories extends ListContentsPlugin
{
    protected function getType()
    {
        return 'dir';
    }

    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'listDirectories';
    }
}
