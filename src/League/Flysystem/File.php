<?php

namespace League\Flysystem;

class File extends Handler
{
    /**
     * Read the file
     *
     * @return  string  file contents
     */
    public function read()
    {
        return $this->filesystem->read($this->path);
    }

    /**
     * Get the file's timestamp
     *
     * @return  int  unix timestamp
     */
    public function getTimestamp()
    {
        return $this->filesystem->getTimestamp($this->path);
    }

    /**
     * Get the file's mimetype
     *
     * @return  string  mimetime
     */
    public function getMimetype()
    {
        return $this->filesystem->getMimetype($this->path);
    }

    /**
     * Get the file size
     *
     * @return  int  file size
     */
    public function getSize()
    {
        return $this->filesystem->getSize($this->path);
    }

    /**
     * Delete the file
     *
     * @return  boolean  success boolean
     */
    public function delete()
    {
        return $this->filesystem->delete($this->path);
    }
}
