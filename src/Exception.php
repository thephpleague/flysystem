<?php

namespace League\Flysystem;

class Exception extends \Exception
{
    /**
     * Wraps exception, used to wrap 3rd party client exceptions inside Flysystem exception
     *
     * @param \Exception $exception Exception thrown from adapter
     *
     * @return Exception
     */
    public static function wrap(\Exception $exception)
    {
        return new self($exception->getMessage(), $exception->getCode(), $exception);
    }
}
