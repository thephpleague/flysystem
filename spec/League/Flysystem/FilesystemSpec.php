<?php

namespace spec\League\Flysystem;

use League\Flysystem\AdapterInterface;
use League\Flysystem\CacheInterface;
use League\Flysystem\Config;
use League\Flysystem\Stub\PluginStub;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FilesystemSpec extends ObjectBehavior
{
    protected $adapter;
    protected $cache;

    public function let(AdapterInterface $adapter, CacheInterface $cache)
    {
        $this->adapter = $adapter;
        $this->cache = $cache;
        $cache->load()->shouldBeCalled();
        $this->beConstructedWith($adapter, $cache);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('League\Flysystem\Filesystem');
        $this->shouldHaveType('League\Flysystem\FilesystemInterface');
    }

    public function it_should_expose_an_adapter()
    {
        $this->getAdapter()->shouldHaveType('League\Flysystem\AdapterInterface');
    }

    public function it_should_expose_a_cache_adapter()
    {
        $this->getCache()->shouldHaveType('League\Flysystem\CacheInterface');
    }

    public function it_should_delegate_flush_cache_calls()
    {
        $this->cache->flush()->shouldBeCalled();
        $this->flushCache();
    }

    public function it_should_delegate_plugin_calls(PluginStub $plugin)
    {
        $plugin->setFilesystem($this)->shouldBeCalled();
        $plugin->getMethod()->willReturn('pluginMethod');
        $plugin->handle()->shouldBeCalled();
        $this->addPlugin($plugin);
        $this->pluginMethod();
    }

    public function it_should_allow_writes()
    {
        $this->cache->has('file')->willReturn(false);
        $this->adapter->write('file', 'contents', Argument::type('League\Flysystem\Config'))->willReturn($cache = [
            'path' => 'file',
            'contents' => 'contents',
        ]);
        $this->cache->updateObject('file', $cache, true)->shouldBeCalled();
        $this->write('file', 'contents')->shouldReturn(true);
    }

    public function it_should_allow_stream_writes()
    {
        $stream = tmpfile();
        $this->cache->has('file')->willReturn(false);
        $this->adapter->writeStream('file', $stream, Argument::type('League\Flysystem\Config'))->willReturn($cache = [
            'path' => 'file',
        ]);

        $this->cache->updateObject('file', $cache + ['contents' => false], true)->shouldBeCalled();
        $this->writeStream('file', $stream)->shouldReturn(true);
        fclose($stream);
    }

    public function it_should_throw_an_exception_when_the_input_is_not_a_resource_during_writeStream()
    {
        $this->cache->has('file')->willReturn(false);
        $this->shouldThrow('InvalidArgumentException')->duringWriteStream('file', 'string');
    }

    public function it_should_throw_an_exception_when_the_input_is_not_a_resource_during_updateStream()
    {
        $this->cache->has('file')->willReturn(true);
        $this->shouldThrow('InvalidArgumentException')->duringUpdateStream('file', 'string');
    }

    public function it_should_return_false_when_writing_to_a_existing_file()
    {
        $this->cache->has('file')->willReturn(false);
        $this->adapter->write('file', 'contents', Argument::type('League\Flysystem\Config'))->willReturn(false);
        $this->write('file', 'contents')->shouldEqual(false);
    }

    public function it_should_return_false_when_writing_a_stream_to_a_existing_file()
    {
        $stream = tmpfile();
        $this->cache->has('file')->willReturn(false);
        $this->adapter->writeStream('file', $stream, Argument::type('League\Flysystem\Config'))->willReturn(false);
        $this->writeStream('file', $stream)->shouldEqual(false);
        fclose($stream);
    }

    public function it_should_forward_updates()
    {
        $this->cache->has('file')->willReturn(true);
        $this->adapter->update('file', 'contents', Argument::type('League\Flysystem\Config'))->willReturn($cache = [
            'path' => 'file',
            'contents' => 'contents',
        ]);
        $this->cache->updateObject('file', $cache, true)->shouldBeCalled();
        $this->update('file', 'contents');
    }

    public function it_should_forward_stream_updates()
    {
        $stream = tmpfile();
        $this->cache->has('file')->willReturn(true);
        $this->adapter->updateStream('file', $stream, Argument::type('League\Flysystem\Config'))->willReturn($cache = [
            'path' => 'file',
        ]);
        $this->cache->updateObject('file', $cache + ['contents' => false], true)->shouldBeCalled();
        $this->updateStream('file', $stream);
        fclose($stream);
    }

    public function it_should_write_when_putting_a_new_file()
    {
        $this->cache->has('file')->willReturn(false);
        $this->adapter->write('file', 'contents', Argument::type('League\Flysystem\Config'))->willReturn($cache = [
            'path' => 'file',
            'contents' => 'contents',
        ]);
        $this->cache->updateObject('file', $cache, true)->shouldBeCalled();
        $this->put('file', 'contents')->shouldReturn(true);
    }

    public function it_should_write_when_putting_a_new_file_using_stream()
    {
        $stream = tmpfile();
        $this->cache->has('file')->willReturn(false);
        $this->adapter->writeStream('file', $stream, Argument::type('League\Flysystem\Config'))->willReturn($cache = [
            'path' => 'file',
        ]);
        $this->cache->updateObject('file', $cache + ['contents' => false], true)->shouldBeCalled();
        $this->putStream('file', $stream)->shouldReturn(true);
        fclose($stream);
    }

    public function it_should_update_when_putting_a_new_file()
    {
        $this->cache->has('file')->willReturn(true);
        $this->adapter->update('file', 'contents', Argument::type('League\Flysystem\Config'))->willReturn($cache = [
            'path' => 'file',
            'contents' => 'contents',
        ]);
        $this->cache->updateObject('file', $cache, true)->shouldBeCalled();
        $this->put('file', 'contents')->shouldReturn(true);
    }

    public function it_should_update_when_putting_a_new_file_using_stream()
    {
        $stream = tmpfile();
        $this->cache->has('file')->willReturn(true);
        $this->adapter->updateStream('file', $stream, Argument::type('League\Flysystem\Config'))->willReturn($cache = [
            'path' => 'file',
        ]);
        $this->cache->updateObject('file', $cache + ['contents' => false], true)->shouldBeCalled();
        $this->putStream('file', $stream)->shouldReturn(true);
        fclose($stream);
    }

    public function it_should_return_false_when_write_fails()
    {
        $this->cache->has('file')->willReturn(false);
        $this->adapter->write('file', 'contents', Argument::type('League\Flysystem\Config'))->willReturn(false);
        $this->write('file', 'contents')->shouldReturn(false);
    }

    public function it_should_return_false_when_stream_write_fails()
    {
        $stream = tmpfile();
        $this->cache->has('file')->willReturn(false);
        $this->adapter->writeStream('file', $stream, Argument::type('League\Flysystem\Config'))->willReturn(false);
        $this->writeStream('file', $stream)->shouldReturn(false);
        fclose($stream);
    }

    public function it_should_return_false_when_update_fails()
    {
        $this->cache->has('file')->willReturn(true);
        $this->adapter->update('file', 'contents', Argument::type('League\Flysystem\Config'))->willReturn(false);
        $this->update('file', 'contents')->shouldReturn(false);
    }

    public function it_should_return_false_when_stream_update_fails()
    {
        $stream = tmpfile();
        $this->cache->has('file')->willReturn(true);
        $this->adapter->updateStream('file', $stream, Argument::type('League\Flysystem\Config'))->willReturn(false);
        $this->updateStream('file', $stream)->shouldReturn(false);
        fclose($stream);
    }

    public function it_should_forward_delete_calls()
    {
        $this->cache->has('file')->willReturn(true);
        $this->cache->delete('file')->shouldBeCalled();
        $this->adapter->delete('file')->willReturn(true);
        $this->delete('file')->shouldReturn(true);
    }

    public function it_should_return_false_when_failing_to_delete_a_file()
    {
        $this->cache->has('file')->willReturn(true);
        $this->adapter->delete('file')->willReturn(false);
        $this->delete('file')->shouldReturn(false);
    }

    public function it_should_store_a_miss_when_a_file_does_not_exists()
    {
        $this->adapter->has('file')->willReturn(false);
        $this->cache->has('file')->willReturn(null);
        $this->cache->storeMiss('file')->shouldBeCalled();
        $this->has('file')->shouldReturn(false);
    }

    public function it_should_return_true_on_has_when_a_file_exists()
    {
        $this->adapter->has('file')->willReturn(true);
        $this->cache->has('file')->willReturn(null);
        $this->cache->updateObject('file', ['path' => 'file'], true)->shouldBeCalled();
        $this->has('file')->shouldReturn(true);
    }

    public function it_should_store_metadata_returned_from_adapter_has_response()
    {
        $this->adapter->has('file')->willReturn($metadata = ['mimetype' => 'text/plain']);
        $this->cache->has('file')->willReturn(null);
        $this->cache->updateObject('file', $metadata, true)->shouldBeCalled();
        $this->has('file')->shouldReturn(true);
    }

    public function it_should_return_cached_contents_during_read()
    {
        $this->cache->has('file')->willReturn(true);
        $this->cache->read('file')->willReturn('contents');
        $this->read('file')->shouldReturn('contents');
    }

    public function it_should_retrieve_contents_when_not_cached_during_read()
    {
        $this->cache->has('file')->willReturn(true);
        $this->cache->read('file')->willReturn(false);
        $this->adapter->read('file')->willReturn($meta = ['contents' => 'contents']);
        $this->cache->updateObject('file', $meta, true)->shouldBeCalled();
        $this->read('file')->shouldReturn('contents');
    }

    public function it_should_return_false_when_read_fails_during_readAndDelete()
    {
        $this->cache->has('file')->willReturn(true);
        $this->cache->read('file')->willReturn(false);
        $this->adapter->read('file')->willReturn(false);
        $this->readAndDelete('file')->shouldReturn(false);
    }

    public function it_should_delete_after_reading_during_readAndDelete()
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
