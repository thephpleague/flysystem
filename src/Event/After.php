<?php

namespace League\Flysystem\Event;

use ErrorException;
use League\Event\AbstractEvent;
use League\Flysystem\FilesystemInterface;

class After extends AbstractEvent
{
    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var mixed
     */
    protected $result;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * @param FilesystemInterface $filesystem
     * @param string              $method
     * @param mixed               $result
     * @param array               $arguments
     */
    public function __construct(FilesystemInterface $filesystem, $method, $result, array $arguments = [])
    {
        $this->filesystem = $filesystem;
        $this->method = $method;
        $this->result = $result;
        $this->arguments = $arguments;
    }

    /**
     * Get the Filesystem instance.
     *
     * @return FilesystemInterface
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function getName()
    {
        $method = $this->getMethod();

        return 'after.'.strtolower($method);
    }

    /**
     * Get the called method.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get the method call result.
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Overwrite the result.
     *
     * @param mixed $result
     *
     * @return $this
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Get the passed arguments.
     *
     * @return array method arguments
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Get an argument by key.
     *
     * @param string $key argument key
     *
     * @return mixed
     *
     * @throws ErrorException
     */
    public function getArgument($key)
    {
        if (! array_key_exists($key, $this->arguments)) {
            throw new ErrorException('Undefined index: '.$key);
        }

        return $this->arguments[$key];
    }
}
