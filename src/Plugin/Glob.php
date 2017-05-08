<?php

namespace League\Flysystem\Plugin;

/**
 * Class Glob
 * @package League\Flysystem\Plugin
 */
class Glob extends AbstractPlugin
{
    /**
     * Get the method name
     *
     * @return  string
     */
    public function getMethod()
    {
        return 'glob';
    }

    /**
     * @param $path
     */
    protected function yieldGlob($path)
    {
        foreach ($this->filesystem->listContents('', true) as $file) {
            if (fnmatch($path, $file['path'])) {
                yield $file;
            }
        }
    }

    /**
     * @param $path
     * @return array
     */
    protected function returnGlob($path)
    {
        $out = array();
        foreach ($this->filesystem->listContents('', true) as $file) {
            if (fnmatch($path, $file['path'])) {
                $out[] = $file;
            }
        }

        return $out;
    }

    /**
     * @param string $path
     * @param bool $useGenerator
     * @return array|void
     */
    public function handle($path = '', $useGenerator = false)
    {
        if (!$useGenerator) {
            return $this->returnGlob($path);
        }

        return $this->yieldGlob($path);
    }
}
