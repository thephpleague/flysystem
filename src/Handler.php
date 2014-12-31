<?php

namespace League\Flysystem;

abstract class Handler
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * Constructor.
     *
     * @param FilesystemInterface $filesystem
     * @param string              $path
     */
    public function __construct(FilesystemInterface $filesystem = null, $path = null)
    {
        $this->path = $path;
        $this->filesystem = $filesystem;
    }

    /**
     * Check whether the entree is a directory.
     *
     * @return bool
     */
    public function isDir()
    {
        return $this->getType() === 'dir';
    }

    /**
     * Check whether the entree is a file.
     *
     * @return bool
     */
    public function isFile()
    {
        return $this->getType() === 'file';
    }

    /**
     * Retrieve the entree type (file|dir).
     *
     * @return string file or dir
     */
    public function getType()
    {
        $metadata = $this->filesystem->getMetadata($this->path);

        return $metadata['type'];
    }

    /**
     * Set the Filesystem object.
     *
     * @param FilesystemInterface $filesystem
     *
     * @return $this
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;

        return $this;
    }

    /**
     * Set the entree path.
     *
     * @param string $path
     *
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Retrieve the entree path.
     *
     * @return string path
     */
    public function getPath()
    {
        return $this->path;
    }
}
