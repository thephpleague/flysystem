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


class Before extends EventAbstract
{
    protected $filesystem;
    protected $method;
    protected $arguments;
    protected $result = false;

    /**
     * @param FilesystemInterface $filesystem
     * @param $method
     * @param array $arguments
     */
    public function __construct(FilesystemInterface $filesystem, $method, array $arguments)
    {
        $this->filesystem = $filesystem;
        $this->method = $method;
        $this->arguments = $arguments;
    }

    /**
     * @return FilesystemInterface
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    public function getName()
    {
        $method = $this->getMethod();

        return 'before.' . strtolower($method);
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function getArgument($key, $default = null)
    {
        if ( ! array_key_exists($key, $this->arguments)) {
            return $default;
        }

        return $this->arguments[$key];
    }

    /**
     * Set an argument value
     *
     * @param string $key
     * @param mixed  $value
     * @return $this
     */
    public function setArgument($key, $value)
    {
        $this->arguments[$key] = $value;

        return $this;
    }

    public function setArguments(array $arguments)
    {
        $this->arguments = array_merge($this->arguments, $arguments);

        return $this;
    }

    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param mixed $result
     */
    public function cancelOperation($result = false)
    {
        $this->setResult($result);
        $this->stopPropagation();
    }
}