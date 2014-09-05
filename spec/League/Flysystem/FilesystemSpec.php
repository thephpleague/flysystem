<?php

namespace spec\League\Flysystem;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use League\Flysystem\AdapterInterface;
use League\Flysystem\CacheInterface;
use League\Flysystem\Stub\PluginStub;
use League\Flysystem\Config;

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
        $this->shouldHaveType('League\Flysystem\FilesystemInterface');
        $this->shouldHaveType('League\Flysystem\ReadInterface');
        $this->shouldHaveType('League\Flysystem\AdapterInterface');
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

    function it_should_allow_writes(Config $config)
    {
        $this->cache->has('file')->willReturn(false);
        $this->adapter->write('file', 'contents', $config)->willReturn($cache = [
            'path' => 'file',
            'contents' => 'contents',
        ]);
        $this->cache->updateObject('file', $cache, true)->shouldBeCalled();
        $this->write('file', 'contents', $config)->shouldReturn(true);
    }

    function it_should_allow_stream_writes(Config $config)
    {
        $stream = tmpfile();
        $this->cache->has('file')->willReturn(false);
        $this->adapter->writeStream('file', $stream, $config)->willReturn($cache = [
            'path' => 'file',
        ]);

        $this->cache->updateObject('file', $cache + ['contents' => false], true)->shouldBeCalled();
        $this->writeStream('file', $stream, $config)->shouldReturn(true);
        fclose($stream);
    }

    function it_should_throw_an_exception_when_the_input_is_not_a_resource_during_writeStream()
    {
        $this->cache->has('file')->willReturn(false);
        $this->shouldThrow('InvalidArgumentException')->duringWriteStream('file', 'string');
    }

    function it_should_throw_an_exception_when_the_input_is_not_a_resource_during_updateStream()
    {
        $this->cache->has('file')->willReturn(true);
        $this->shouldThrow('InvalidArgumentException')->duringUpdateStream('file', 'string');
    }

    function it_should_return_false_when_writing_to_a_existing_file(Config $config)
    {
        $this->cache->has('file')->willReturn(false);
        $this->adapter->write('file', 'contents', $config)->willReturn(false);
        $this->write('file', 'contents', $config)->shouldEqual(false);
    }

    function it_should_return_false_when_writing_a_stream_to_a_existing_file(Config $config)
    {
        $stream = tmpfile();
        $this->cache->has('file')->willReturn(false);
        $this->adapter->writeStream('file', $stream, $config)->willReturn(false);
        $this->writeStream('file', $stream, $config)->shouldEqual(false);
        fclose($stream);
    }

    function it_should_forward_updates(Config $config)
    {
        $this->cache->has('file')->willReturn(true);
        $this->adapter->update('file', 'contents', $config)->willReturn($cache = [
            'path' => 'file',
            'contents' => 'contents',
        ]);
        $this->cache->updateObject('file', $cache, true)->shouldBeCalled();
        $this->update('file', 'contents', $config);
    }

    function it_should_forward_stream_updates(Config $config)
    {
        $stream = tmpfile();
        $this->cache->has('file')->willReturn(true);
        $this->adapter->updateStream('file', $stream, $config)->willReturn($cache = [
            'path' => 'file',
        ]);
        $this->cache->updateObject('file', $cache + ['contents' => false], true)->shouldBeCalled();
        $this->updateStream('file', $stream, $config);
        fclose($stream);
    }

    function it_should_write_when_putting_a_new_file(Config $config)
    {
        $this->cache->has('file')->willReturn(false);
        $this->adapter->write('file', 'contents', $config)->willReturn($cache = [
            'path' => 'file',
            'contents' => 'contents',
        ]);
        $this->cache->updateObject('file', $cache, true)->shouldBeCalled();
        $this->put('file', 'contents', $config)->shouldReturn(true);
    }

    function it_should_write_when_putting_a_new_file_using_stream(Config $config)
    {
        $stream = tmpfile();
        $this->cache->has('file')->willReturn(false);
        $this->adapter->writeStream('file', $stream, $config)->willReturn($cache = [
            'path' => 'file',
        ]);
        $this->cache->updateObject('file', $cache + ['contents' => false], true)->shouldBeCalled();
        $this->putStream('file', $stream, $config)->shouldReturn(true);
        fclose($stream);
    }

    function it_should_update_when_putting_a_new_file(Config $config)
    {
        $this->cache->has('file')->willReturn(true);
        $this->adapter->update('file', 'contents', $config)->willReturn($cache = [
            'path' => 'file',
            'contents' => 'contents',
        ]);
        $this->cache->updateObject('file', $cache, true)->shouldBeCalled();
        $this->put('file', 'contents', $config)->shouldReturn(true);
    }

    function it_should_update_when_putting_a_new_file_using_stream(Config $config)
    {
        $stream = tmpfile();
        $this->cache->has('file')->willReturn(true);
        $this->adapter->updateStream('file', $stream, $config)->willReturn($cache = [
            'path' => 'file',
        ]);
        $this->cache->updateObject('file', $cache + ['contents' => false], true)->shouldBeCalled();
        $this->putStream('file', $stream, $config)->shouldReturn(true);
        fclose($stream);
    }

    function it_should_return_false_when_write_fails(Config $config)
    {
        $this->cache->has('file')->willReturn(false);
        $this->adapter->write('file', 'contents', $config)->willReturn(false);
        $this->write('file', 'contents', $config)->shouldReturn(false);
    }

    function it_should_return_false_when_stream_write_fails(Config $config)
    {
        $stream = tmpfile();
        $this->cache->has('file')->willReturn(false);
        $this->adapter->writeStream('file', $stream, $config)->willReturn(false);
        $this->writeStream('file', $stream, $config)->shouldReturn(false);
        fclose($stream);
    }

    function it_should_return_false_when_update_fails(Config $config)
    {
        $this->cache->has('file')->willReturn(true);
        $this->adapter->update('file', 'contents', $config)->willReturn(false);
        $this->update('file', 'contents', $config)->shouldReturn(false);
    }

    function it_should_return_false_when_stream_update_fails(Config $config)
    {
        $stream = tmpfile();
        $this->cache->has('file')->willReturn(true);
        $this->adapter->updateStream('file', $stream, $config)->willReturn(false);
        $this->updateStream('file', $stream, $config)->shouldReturn(false);
        fclose($stream);
    }

    function it_should_forward_delete_calls()
    {
        $this->cache->has('file')->willReturn(true);
        $this->cache->delete('file')->shouldBeCalled();
        $this->adapter->delete('file')->willReturn(true);
        $this->delete('file')->shouldReturn(true);
    }

    function it_should_return_false_when_failing_to_delete_a_file()
    {
        $this->cache->has('file')->willReturn(true);
        $this->adapter->delete('file')->willReturn(false);
        $this->delete('file')->shouldReturn(false);
    }

    function it_should_store_a_miss_when_a_file_does_not_exists()
    {
        $this->adapter->has('file')->willReturn(false);
        $this->cache->has('file')->willReturn(null);
        $this->cache->storeMiss('file')->shouldBeCalled();
        $this->has('file')->shouldReturn(false);
    }

    function it_should_return_true_on_has_when_a_file_exists()
    {
        $this->adapter->has('file')->willReturn(true);
        $this->cache->has('file')->willReturn(null);
        $this->cache->updateObject('file', [], true)->shouldBeCalled();
        $this->has('file')->shouldReturn(true);
    }

    function it_should_store_metadata_returned_from_adapter_has_response()
    {
        $this->adapter->has('file')->willReturn($metadata = ['mimetype' => 'text/plain']);
        $this->cache->has('file')->willReturn(null);
        $this->cache->updateObject('file', $metadata, true)->shouldBeCalled();
        $this->has('file')->shouldReturn(true);
    }

    function it_should_return_cached_contents_during_read()
    {
        $this->cache->has('file')->willReturn(true);
        $this->cache->read('file')->willReturn('contents');
        $this->read('file')->shouldReturn('contents');
    }

    function it_should_retrieve_contents_when_not_cached_during_read()
    {
        $this->cache->has('file')->willReturn(true);
        $this->cache->read('file')->willReturn(false);
        $this->adapter->read('file')->willReturn($meta = ['contents' => 'contents']);
        $this->cache->updateObject('file', $meta, true)->shouldBeCalled();
        $this->read('file')->shouldReturn('contents');
    }

    function it_should_return_false_when_read_fails_during_readAndDelete()
    {
        $this->cache->has('file')->willReturn(true);
        $this->cache->read('file')->willReturn(false);
        $this->adapter->read('file')->willReturn(false);
        $this->readAndDelete('file')->shouldReturn(false);
    }

    function it_should_delete_after_reading_during_readAndDelete()
    {
        $this->cache->has('file')->willReturn(true);
        $this->cache->read('file')->willReturn(false);
        $this->adapter->read('file')->willReturn($metadata = ['contents' => 'contents']);
        $this->cache->updateObject('file', $metadata, true)->shouldBeCalled();
        $this->adapter->delete('file')->shouldBeCalled();
        $this->cache->delete('file')->shouldBeCalled();
        $this->readAndDelete('file')->shouldReturn('contents');
    }
}
