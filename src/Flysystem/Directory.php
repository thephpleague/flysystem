<?php

namespace Flysystem;

class Directory extends Handler
{
    public function delete()
    {
        $this->filesystem->deleteDir($this->path);
    }

    public function getContents($recursive = false)
    {
        return $this->filesystem->listContents($this->path, $recursive);
    }
}
