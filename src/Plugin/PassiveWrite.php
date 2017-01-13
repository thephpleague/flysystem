<?php

namespace League\Flysystem\Plugin;

use League\Flysystem\FileExistsException;

class PassiveWrite extends AbstractPlugin
{
    /**
     * @inheritdoc
     */
    public function getMethod()
    {
        return 'passiveWrite';
    }

    /**
     * Writes a file, passing if it already existing.
     *
     * @param string $path     Path to the file to write.
     * @param string $contents Contents to write.
     * @param array  $config   Config options for the adapter.
     *
     * @return bool True on success, false on failure.
     */
    public function handle($path, $contents, $config=[])
    {
        try {
            $written = $this->filesystem->write($path, $contents, $config);
        } catch (FileExistsException $e) {
            // The destination path exists. Don't write.
            $written = true;
        }

        return $written;
    }
}
