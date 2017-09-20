<?php

namespace League\Flysystem\Filter;

use League\Flysystem\UnsupportedFilterException;

class ModifiedBeforeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \DateTime
     */
    private $now;
    /**
     * @var \DateTime
     */
    private $future;
    /**
     * @var \DateTime
     */
    private $past;

    public function setUp()
    {
        $this->future = new \DateTime('first day of next month');
        $this->now = new \DateTime('now');
        $this->past = new \DateTime('first day of previous month');
    }

    public function testPickFileModifiedBeforeDate()
    {
        $fileInfoActual = $this->prophesize('League\Flysystem\FilterFileInfo');
        $fileInfoPast = $this->prophesize('League\Flysystem\FilterFileInfo');
        $fileInfoFuture = $this->prophesize('League\Flysystem\FilterFileInfo');

        $fileInfoActual->getTimestamp()->willReturn(date_timestamp_get($this->now));
        $fileInfoPast->getTimestamp()->willReturn(date_timestamp_get($this->past));
        $fileInfoFuture->getTimestamp()->willReturn(date_timestamp_get($this->future));

        $this->assertTrue(
            (new ModifiedBefore($this->now))->isSatisfiedBy($fileInfoPast->reveal())
        );
        $this->assertFalse(
            (new ModifiedBefore($this->now))->isSatisfiedBy($fileInfoActual->reveal())
        );
        $this->assertFalse(
            (new ModifiedBefore($this->now))->isSatisfiedBy($fileInfoFuture->reveal())
        );
    }

    /**
     * @expectedException League\Flysystem\UnsupportedFilterException
     */
    public function testWillNotFilterWhenTimestampIsNotSupportedByFileInfo()
    {
        $fileInfo = $this->prophesize('League\Flysystem\FilterFileInfo');

        $fileInfo->getTimestamp()->willThrow(new UnsupportedFilterException());

        (new ModifiedBefore($this->now))->isSatisfiedBy($fileInfo->reveal());
    }
}
