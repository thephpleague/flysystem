<?php

namespace spec\League\Flysystem;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use League\Flysystem\UnsupportedFilterException;

class FilterFileInfoSpec extends ObjectBehavior
{
    function it_could_create_itself_from_normalized_file_info()
    {
        $timestamp = date_timestamp_get(new \DateTime('now'));
        $normalizedFileInfo = [
            'type' => 'file',
            'path' => 'file.txt',
            'timestamp' => $timestamp,
            'size' => 21981278127,
            'extension' => 'txt',
            'filename' => 'file',
            'visibility' => 'public'
        ];

        $this->beConstructedThrough('createFromNormalized', [$normalizedFileInfo]);

        $this->getType()->shouldReturn('file');
        $this->getPath()->shouldReturn('file.txt');
        $this->getTimestamp()->shouldReturn($timestamp);
        $this->getSize()->shouldReturn(21981278127);
        $this->getExtension()->shouldReturn('txt');
        $this->getFilename()->shouldReturn('file');
        $this->getVisibility()->shouldReturn('public');
    }

    function it_should_inform_about_not_supporting_filter_while_trying_to_retreive_specific_information()
    {
        $type = 'file';
        $path = null;
        $timestamp = null;
        $size = null;
        $extension = 'txt';
        $filename = 'file';
        $visibility = 'public';

        $this->beConstructedWith(
            $type,
            $path,
            $timestamp,
            $size,
            $extension,
            $filename,
            $visibility
        );

        $this->getType()->shouldReturn('file');
        $this->shouldThrow(new UnsupportedFilterException('Filtering by path is not supported.'))
            ->during('getPath');
        $this->shouldThrow(new UnsupportedFilterException('Filtering by timestamp is not supported.'))
            ->during('getTimestamp');
        $this->shouldThrow(new UnsupportedFilterException('Filtering by size is not supported.'))
            ->during('getSize');
        $this->getExtension()->shouldReturn('txt');
        $this->getFilename()->shouldReturn('file');
        $this->getVisibility()->shouldReturn('public');

    }
}
