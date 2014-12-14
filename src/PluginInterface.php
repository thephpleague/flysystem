<?php

namespace League\Flysystem;

interface PluginInterface
{
    /**
     * Get the method name
     *
     * @return string
     */
    public function getMethod();

    /**
     * Set the Filesystem object
     *
     * @param FilesystemInterface $filesystem
     *
     * @return void
     */
    public function setFilesystem(FilesystemInterface $filesystem);
}
