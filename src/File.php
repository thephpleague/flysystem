<?php

namespace League\Flysystem;

class File extends Handler
{
    /**
     * Read the file
     *
     * @return string file contents
     */
    public function read()
    {
        return $this->filesystem->read($this->path);
    }

    /**
     * Read the file as a stream
     *
     * @return resource file stream
     */
    public function readStream()
    {
        return $this->filesystem->readStream($this->path);
    }

    /**
     * Update the file contents
     *
     * @param string $content
     *
     * @return boolean success boolean
     */
    public function update($content)
    {
        return $this->filesystem->update($this->path, $content);
    }

    /**
     * Update the file contents with a stream
     *
     * @param resource $resource
     *
     * @return boolean success boolean
     */
    public function updateStream($resource)
    {
        return $this->filesystem->updateStream($this->path, $resource);
    }

    /**
     * Get the file's timestamp
     *
     * @return int unix timestamp
     */
    public function getTimestamp()
    {
        return $this->filesystem->getTimestamp($this->path);
    }

    /**
     * Get the file's mimetype
     *
     * @return string mimetime
     */
    public function getMimetype()
    {
        return $this->filesystem->getMimetype($this->path);
    }

    /**
     * Get the file size
     *
     * @return int file size
     */
    public function getSize()
    {
        return $this->filesystem->getSize($this->path);
    }

    /**
     * Delete the file
     *
     * @return boolean success boolean
     */
    public function delete()
    {
        return $this->filesystem->delete($this->path);
    }
}
