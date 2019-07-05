<?php

namespace League\Flysystem\Plugin;

abstract class ListContentsPlugin extends AbstractPlugin
{
    /**
     * Returns the type of contents, it can be file or dir.
     * 
     * @return string
     */
    abstract protected function getType();

    /**
     * List all files or subdirecotries in the directory.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array
     */
    public function handle($directory = '', $recursive = false)
    {
        $contents = $this->filesystem->listContents($directory, $recursive);

        $type = $this->getType();
        $filter = function ($object) use($type) {
            return $object['type'] === $type;
        };

        return array_values(array_filter($contents, $filter));
    }
}
