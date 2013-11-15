<?php

namespace Flysystem;

interface PluginInterface
{
    /**
     * Get the method name
     *
     * @return  string
     */
    public function getMethod();

    /**
     * Handle the plugin call
     */
    public function handle();

    /**
     * Set the Filesystem object
     *
     * @param  FilesystemInterface  $filesystem
     */
    public function setFilesystem(FilesystemInterface $filesystem);
}