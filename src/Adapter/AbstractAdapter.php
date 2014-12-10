<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\AdapterInterface;

abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * @var  string  $prefixPrefix  path prefix
     */
    protected $pathPrefix;

    /**
     * @var  string  $pathSeparator
     */
    protected $pathSeparator = '/';

    /**
     * @var string
     */
    protected $alias;

    /**
     * Set the path prefix
     *
     * @param   string  $prefix
     * @return  self
     */
    public function setPathPrefix($prefix)
    {
        $is_empty = empty($prefix);

        if (! $is_empty) {
            $prefix = rtrim($prefix, $this->pathSeparator) . $this->pathSeparator;
        }

        $this->pathPrefix = $is_empty ? null : $prefix;
    }

    /**
     * Get the path prefix
     *
     * @return  string  path prefix
     */
    public function getPathPrefix()
    {
        return $this->pathPrefix;
    }

    /**
     * Prefix a path
     *
     * @param   string  $path
     * @return  string  prefixed path
     */
    public function applyPathPrefix($path)
    {
        $path = ltrim($path, '\\/');

        if (strlen($path) === 0) {
            return $this->getPathPrefix() ?: '';
        }

        if ($prefix = $this->getPathPrefix()) {
            $path = $prefix . $path;
        }

        return $path;
    }

    /**
     * Remove a path prefix
     *
     * @param   string  $path
     * @return  string  path without the prefix
     */
    public function removePathPrefix($path)
    {
        if ($this->pathPrefix === null) {
            return $path;
        }

        $length = strlen($this->pathPrefix);

        return substr($path, $length);
    }

    /**
     * @param $alias
     * @return AdapterInterface|void
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasAlias()
    {
        return !is_null($this->alias);
    }

    /**
     * @return null|string
     */
    public function getAlias()
    {
        return $this->alias;
    }
}
