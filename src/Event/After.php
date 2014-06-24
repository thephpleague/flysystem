<?php
/**
 * Created by PhpStorm.
 * User: FrenkyNet
 * Date: 23/06/14
 * Time: 14:52
 */

namespace League\Flysystem\Event;

use League\Event\EventAbstract;
use League\Flysystem\FilesystemInterface;


class After extends EventAbstract
{
    /**
     * @var  FilesystemInterface  $filesystem
     */
    protected $filesystem;

    /**
     * @var  string  $method
     */
    protected $method;

    /**
     * @var  mixed  $result
     */
    protected $result;

    /**
     * @param FilesystemInterface $filesystem
     * @param $method
     * @param mixed $result
     */
    public function __construct(FilesystemInterface $filesystem, $method, $result)
    {
        $this->filesystem = $filesystem;
        $this->method = $method;
        $this->result = $result;
    }

    /**
     * Get the Filesystem instance
     *
     * @return FilesystemInterface
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }


    /**
     * Get the event name
     *
     * @return string
     */
    public function getName()
    {
        $method = $this->getMethod();

        return 'after.' . strtolower($method);
    }

    /**
     * Get the called method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get the method call result
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Overwrite the result
     *
     * @param mixed $result
     * @return $this
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }
}
