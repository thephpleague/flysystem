<?php

namespace League\Flysystem;

class Directory extends Handler
{
    /**
     * Delete the directory
     *
     * @return boolean
     */
    public function delete()
    {
        return $this->filesystem->deleteDir($this->path);
    }

    /**
     * List the directory contents
     *
     * @param boolean $recursive
     *
     * @return array|boolean directory contents or false
     */
    public function getContents($recursive = false)
    {
        return $this->filesystem->listContents($this->path, $recursive);
    }
}
