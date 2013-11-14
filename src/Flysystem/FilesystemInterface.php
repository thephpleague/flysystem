<?php

namespace Flysystem;

interface FilesystemInterface extends AdapterInterface
{
    public function put($path, $contents, $visibility = null);
}