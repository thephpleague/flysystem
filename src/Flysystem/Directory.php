<?php

namespace Flysystem;

class Directory extends Handler
{
    /**
     * Delete the directory
     *
     * @return  void
     */
    public function delete()
    {
        $this->filesystem->deleteDir($this->path);
    }

    /**
     * List the directory contents
     *
     * @param   boolean        $recursive
     * @return  array|boolean  directort contents or false
     */
    public function getContents($recursive = false)
    {
        return $this->filesystem->listContents($this->path, $recursive);
    }
}
