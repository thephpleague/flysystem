<?php

namespace League\Flysystem\Plugin;

use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;
use LogicException;

trait PluggableTrait
{
    /**
     * @var array
     */
    protected $plugins = [];

    /**
     * Register a plugin.
     *
     * @param PluginInterface $plugin
     *
     * @return $this
     */
    public function addPlugin(PluginInterface $plugin)
    {
        $this->plugins[$plugin->getMethod()] = $plugin;

        return $this;
    }

    /**
     * Register a plugin.
     *
     * @param string $method
     *
     * @throws LogicException
     *
     * @return PluginInterface $plugin
     */
    protected function findPlugin($method)
    {
        if (! isset($this->plugins[$method])) {
            throw new PluginNotFoundException('Plugin not found for method: '.$method);
        }

        if (! method_exists($this->plugins[$method], 'handle')) {
            throw new LogicException(get_class($this->plugins[$method]).' does not have a handle method.');
        }

        return $this->plugins[$method];
    }

    /**
     * Invoke a plugin by method name.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    protected function invokePlugin($method, array $arguments, FilesystemInterface $filesystem)
    {
        $plugin = $this->findPlugin($method);
        $plugin->setFilesystem($filesystem);
        $callback = [$plugin, 'handle'];

        return call_user_func_array($callback, $arguments);
    }
}
