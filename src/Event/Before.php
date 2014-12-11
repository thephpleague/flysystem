<?php

namespace League\Flysystem\Event;

use League\Event\AbstractEvent;
use League\Flysystem\FilesystemInterface;

class Before extends AbstractEvent
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
     * @var  array  $arguments
     */
    protected $arguments;

    /**
     * @var  mixed  $result
     */
    protected $result = false;

    /**
     * @param FilesystemInterface $filesystem
     * @param string              $method
     * @param array               $arguments
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

    /**
     * Get the event name
     *
     * @return string event name
     */
    public function getName()
    {
        $method = $this->getMethod();

        return 'before.'.strtolower($method);
    }

    /**
     * Get the called method name
     *
     * @return string method
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get the passed arguments
     *
     * @return array method arguments
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Get an argument by key
     *
     * @param string $key     argument key
     * @param mixed  $default default return value
     *
     * @return mixed
     */
    public function getArgument($key, $default = null)
    {
        if (! array_key_exists($key, $this->arguments)) {
            return $default;
        }

        return $this->arguments[$key];
    }

    /**
     * Set an argument value
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setArgument($key, $value)
    {
        $this->arguments[$key] = $value;

        return $this;
    }

    /**
     * Set the arguments
     *
     * @param array $arguments
     *
     * @return self
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = array_merge($this->arguments, $arguments);

        return $this;
    }

    /**
     * Set the result, used when the operation is canceled
     *
     * @param mixed $result
     *
     * @return self
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Get the result, used when the operation is canceled
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Cancel the operation
     *
     * @param mixed $result
     *
     * @return void
     */
    public function cancelOperation($result = false)
    {
        $this->setResult($result);
        $this->stopPropagation();
    }
}
