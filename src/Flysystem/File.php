<?php

namespace Flysystem;

class File extends Handler
{
    public function read()
    {
        return $this->filesystem->read($this->path);
    }

    public function getTimestamp()
    {
        return $this->filesystem->getTimestamp($this->path);
    }

    public function getMimetype()
    {
        return $this->filesystem->getMimetype($this->path);
    }

    public function getSize()
    {
        return $this->filesystem->getSize($this->path);
    }

    public function delete()
    {
        $this->filesystem->delete($this->path);
    }
}
