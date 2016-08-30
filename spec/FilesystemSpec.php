<?php

namespace spec\League\Flysystem;

use League\Flysystem\FilterFileInfo;
use League\Flysystem\UnsupportedFilterException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FilesystemSpec extends ObjectBehavior
{
    protected $adapter;
    protected $cache;

    /**
     * @param League\Flysystem\AdapterInterface $adapter
     */
    public function let($adapter)
    {
        $this->adapter = $adapter;
        $this->beConstructedWith($adapter);
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

    /**
     * @param League\Flysystem\Stub\PluginStub $plugin
     */
    public function it_should_delegate_plugin_calls($plugin)
    {
        $plugin->setFilesystem($this)->shouldBeCalled();
        $plugin->getMethod()->willReturn('pluginMethod');
        $plugin->handle()->shouldBeCalled();
        $this->addPlugin($plugin);
        $this->pluginMethod();
    }

    public function it_should_allow_writes()
    {
        $this->adapter->has('file')->willReturn(false);
        $this->adapter->write('file', 'contents', Argument::type('League\Flysystem\Config'))->willReturn($cache = [
            'path' => 'file',
            'contents' => 'contents',
        ]);
        $this->write('file', 'contents')->shouldReturn(true);
    }

    public function it_should_allow_stream_writes()
    {
        $stream = tmpfile();
        $this->adapter->has('file')->willReturn(false);
        $this->adapter->writeStream('file', $stream, Argument::type('League\Flysystem\Config'))->willReturn($cache = [
            'path' => 'file',
        ]);

        $this->writeStream('file', $stream)->shouldReturn(true);
        fclose($stream);
    }

    public function it_should_throw_an_exception_when_the_input_is_not_a_resource_during_writeStream()
    {
        $this->adapter->has('file')->willReturn(false);
        $this->shouldThrow('InvalidArgumentException')->duringWriteStream('file', 'string');
    }

    public function it_should_throw_an_exception_when_the_input_is_not_a_resource_during_updateStream()
    {
        $this->adapter->has('file')->willReturn(true);
        $this->shouldThrow('InvalidArgumentException')->duringUpdateStream('file', 'string');
    }

    public function it_should_return_false_when_writing_to_a_existing_file()
    {
        $this->adapter->has('file')->willReturn(false);
        $this->adapter->write('file', 'contents', Argument::type('League\Flysystem\Config'))->willReturn(false);
        $this->write('file', 'contents')->shouldEqual(false);
    }

    public function it_should_return_false_when_writing_a_stream_to_a_existing_file()
    {
        $stream = tmpfile();
        $this->adapter->has('file')->willReturn(false);
        $this->adapter->writeStream('file', $stream, Argument::type('League\Flysystem\Config'))->willReturn(false);
        $this->writeStream('file', $stream)->shouldEqual(false);
        fclose($stream);
    }

    public function it_should_forward_updates()
    {
        $this->adapter->has('file')->willReturn(true);
        $this->adapter->update('file', 'contents', Argument::type('League\Flysystem\Config'))->willReturn($cache = [
            'path' => 'file',
            'contents' => 'contents',
        ]);
        $this->update('file', 'contents');
    }

    public function it_should_forward_stream_updates()
    {
        $stream = tmpfile();
        $this->adapter->has('file')->willReturn(true);
        $this->adapter->updateStream('file', $stream, Argument::type('League\Flysystem\Config'))->willReturn($cache = [
            'path' => 'file',
        ]);
        $this->updateStream('file', $stream);
        fclose($stream);
    }

    public function it_should_write_when_putting_a_new_file()
    {
        $this->adapter->has('file')->willReturn(false);
        $this->adapter->write('file', 'contents', Argument::type('League\Flysystem\Config'))->willReturn($cache = [
            'path' => 'file',
            'contents' => 'contents',
        ]);
        $this->put('file', 'contents')->shouldReturn(true);
    }

    public function it_should_write_when_putting_a_new_file_using_stream()
    {
        $stream = tmpfile();
        $this->adapter->has('file')->willReturn(false);
        $this->adapter->writeStream('file', $stream, Argument::type('League\Flysystem\Config'))->willReturn($cache = [
            'path' => 'file',
        ]);
        $this->putStream('file', $stream)->shouldReturn(true);
        fclose($stream);
    }

    public function it_should_update_when_putting_a_new_file()
    {
        $this->adapter->has('file')->willReturn(true);
        $this->adapter->update('file', 'contents', Argument::type('League\Flysystem\Config'))->willReturn($cache = [
            'path' => 'file',
            'contents' => 'contents',
        ]);
        $this->put('file', 'contents')->shouldReturn(true);
    }

    public function it_should_update_when_putting_a_new_file_using_stream()
    {
        $stream = tmpfile();
        $this->adapter->has('file')->willReturn(true);
        $this->adapter->updateStream('file', $stream, Argument::type('League\Flysystem\Config'))->willReturn($cache = [
            'path' => 'file',
        ]);
        $this->putStream('file', $stream)->shouldReturn(true);
        fclose($stream);
    }

    public function it_should_return_false_when_write_fails()
    {
        $this->adapter->has('file')->willReturn(false);
        $this->adapter->write('file', 'contents', Argument::type('League\Flysystem\Config'))->willReturn(false);
        $this->write('file', 'contents')->shouldReturn(false);
    }

    public function it_should_return_false_when_stream_write_fails()
    {
        $stream = tmpfile();
        $this->adapter->has('file')->willReturn(false);
        $this->adapter->writeStream('file', $stream, Argument::type('League\Flysystem\Config'))->willReturn(false);
        $this->writeStream('file', $stream)->shouldReturn(false);
        fclose($stream);
    }

    public function it_should_return_false_when_update_fails()
    {
        $this->adapter->has('file')->willReturn(true);
        $this->adapter->update('file', 'contents', Argument::type('League\Flysystem\Config'))->willReturn(false);
        $this->update('file', 'contents')->shouldReturn(false);
    }

    public function it_should_return_false_when_stream_update_fails()
    {
        $stream = tmpfile();
        $this->adapter->has('file')->willReturn(true);
        $this->adapter->updateStream('file', $stream, Argument::type('League\Flysystem\Config'))->willReturn(false);
        $this->updateStream('file', $stream)->shouldReturn(false);
        fclose($stream);
    }

    public function it_should_forward_delete_calls()
    {
        $this->adapter->has('file')->willReturn(true);
        $this->adapter->delete('file')->willReturn(true);
        $this->delete('file')->shouldReturn(true);
    }

    public function it_should_return_false_when_failing_to_delete_a_file()
    {
        $this->adapter->has('file')->willReturn(true);
        $this->adapter->delete('file')->willReturn(false);
        $this->delete('file')->shouldReturn(false);
    }

    public function it_should_return_true_on_has_when_a_file_exists()
    {
        $this->adapter->has('file')->willReturn(true);
        $this->has('file')->shouldReturn(true);
    }

    public function it_should_return_false_when_read_fails_during_readAndDelete()
    {
        $this->adapter->has('file')->willReturn(true);
        $this->adapter->read('file')->willReturn(false);
        $this->readAndDelete('file')->shouldReturn(false);
    }

    public function it_should_delete_after_reading_during_readAndDelete()
    {
        $this->adapter->has('file')->willReturn(true);
        $this->adapter->read('file')->willReturn(['contents' => 'contents']);
        $this->adapter->delete('file')->shouldBeCalled();
        $this->readAndDelete('file')->shouldReturn('contents');
    }

    public function it_should_return_false_when_checking_if_an_empty_filename_exists()
    {
        $this->has('')->shouldReturn(false);
    }

    /**
     * @param League\Flysystem\FilterCriteriaInterface $filterCriteria
     * @param League\Flysystem\FilteringReadInterface $adapter
     */
    public function it_should_try_to_outsource_filtering_to_adapter($filterCriteria, $adapter)
    {
        $this->adapter = $adapter;
        $this->beConstructedWith($adapter);

        $filteredContents = [
            [
                'type' => 'file',
                'path' => 'file.txt',
                'timestamp' => date_timestamp_get(new \DateTime('now')),
                'size' => 21981278127,
                'dirname' => '',
                'basename' => 'file',
                'extension' => 'txt',
                'filename' => 'file.txt'
            ]
        ];

        $this->adapter->listFilteredContents($filterCriteria, '', false)->willReturn($filteredContents);

        $this->listFilteredContents($filterCriteria, '', false)->shouldReturn($filteredContents);
    }

    /**
     * @param League\Flysystem\FilterCriteriaInterface $filterCriteria
     * @param League\Flysystem\FilteringReadInterface $adapter
     */
    public function it_should_throw_exception_if_some_cirteria_is_unsupported_by_adapter($filterCriteria, $adapter)
    {
        $this->adapter = $adapter;
        $this->beConstructedWith($adapter);

        $this->adapter->listFilteredContents($filterCriteria, '', false)->willThrow(new UnsupportedFilterException());

        $this->shouldThrow(new UnsupportedFilterException())->during(
            'listFilteredContents',
            [
                $filterCriteria,
                '',
                false
            ]
        );
    }

    /**
     * @param League\Flysystem\FilterCriteriaInterface $filterCriteria
     */
    public function it_should_filter_list_itself_if_adapter_not_support_filtering($filterCriteria)
    {
        $matchingFileInfo = [
            'type' => 'file',
            'path' => 'file.txt',
            'timestamp' => date_timestamp_get(new \DateTime('now')),
            'size' => 21981278127,
            'dirname' => '',
            'basename' => 'file',
            'extension' => 'txt',
            'filename' => 'file.txt'
        ];
        $notMatchingFileInfo = [
            'type' => 'file2',
            'path' => 'file2.txt',
            'timestamp' => date_timestamp_get(new \DateTime('now')),
            'size' => 219878127,
            'dirname' => '',
            'basename' => 'file2',
            'filename' => 'file2.txt'
        ];

        $unfilteredContents = [
            $matchingFileInfo,
            $notMatchingFileInfo
        ];
        $filteredContents = [
            $matchingFileInfo
        ];

        $this->adapter->listContents('', false)->willReturn($unfilteredContents);

        $filterCriteria->isSatisfiedBy(FilterFileInfo::createFromNormalized($notMatchingFileInfo))->willReturn(false);
        $filterCriteria->isSatisfiedBy(FilterFileInfo::createFromNormalized($matchingFileInfo))->willReturn(true);

        $this->listFilteredContents($filterCriteria, '', false)->shouldReturn($filteredContents);
    }

    /**
     * @param League\Flysystem\FilterCriteriaInterface $filterCriteria
     */
    public function it_should_throw_exception_if_some_cirteria_is_unsupported_while_self_filtering($filterCriteria)
    {
        $notFullFileInfo = [
            'type' => 'file',
            'path' => 'file.txt',
            'timestamp' => date_timestamp_get(new \DateTime('now')),
            'size' => null,
            'dirname' => '',
            'basename' => 'file',
            'extension' => 'txt',
            'filename' => 'file.txt'
        ];

        $unfilteredContents = [
            $notFullFileInfo
        ];

        $this->adapter->listContents('', false)->willReturn($unfilteredContents);

        $filterCriteria->isSatisfiedBy(FilterFileInfo::createFromNormalized($notFullFileInfo))
            ->willThrow(new UnsupportedFilterException());

        $this->shouldThrow(new UnsupportedFilterException())->during('listFilteredContents', [$filterCriteria]);
    }
}
