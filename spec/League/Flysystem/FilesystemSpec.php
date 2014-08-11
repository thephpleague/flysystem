<?php

namespace spec\League\Flysystem;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use League\Flysystem\AdapterInterface;
use League\Flysystem\CacheInterface;
use League\Flysystem\Stub\PluginStub;

class FilesystemSpec extends ObjectBehavior
{
    protected $adapter;
    protected $cache;

    function let(AdapterInterface $adapter, CacheInterface $cache)
    {
        $this->adapter = $adapter;
        $this->cache = $cache;
        $cache->load()->shouldBeCalled();
        $this->beConstructedWith($adapter, $cache);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('League\Flysystem\Filesystem');
    }

    function it_should_expose_an_adapter()
    {
        $this->getAdapter()->shouldHaveType('League\Flysystem\AdapterInterface');
    }

    function it_should_expose_a_cache_adapter()
    {
        $this->getCache()->shouldHaveType('League\Flysystem\CacheInterface');
    }

    function it_should_delegate_flush_cache_calls()
    {
        $this->cache->flush()->shouldBeCalled();
        $this->flushCache();
    }

    function it_should_delegate_plugin_calls(PluginStub $plugin)
    {
        $plugin->setFilesystem($this)->shouldBeCalled();
        $plugin->getMethod()->willReturn('pluginMethod');
        $plugin->handle()->shouldBeCalled();
        $this->addPlugin($plugin);
        $this->pluginMethod();
    }
}
