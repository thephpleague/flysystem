<?php

namespace League\Flysystem\Filter;

use League\Flysystem\UnsupportedFilterException;

class SizeSmallerThanTest extends \PHPUnit_Framework_TestCase
{
    public function testPickFilesWithSameSize()
    {
        $fileInfoSmall = $this->prophesize('League\Flysystem\FilterFileInfo');
        $fileInfoMedium = $this->prophesize('League\Flysystem\FilterFileInfo');
        $fileInfoBig = $this->prophesize('League\Flysystem\FilterFileInfo');

        $fileInfoSmall->getSize()->willReturn(256);
        $fileInfoMedium->getSize()->willReturn(512);
        $fileInfoBig->getSize()->willReturn(1024);

        $this->assertTrue(
            (new SizeSmallerThan(512))->isSatisfiedBy($fileInfoSmall->reveal())
        );
        $this->assertFalse(
            (new SizeSmallerThan(512))->isSatisfiedBy($fileInfoMedium->reveal())
        );
        $this->assertFalse(
            (new SizeSmallerThan(512))->isSatisfiedBy($fileInfoBig->reveal())
        );
    }

    /**
     * @expectedException League\Flysystem\UnsupportedFilterException
     */
    public function testWillNotFilterWhenSizeIsNotSupportedByFileInfo()
    {
        $fileInfo = $this->prophesize('League\Flysystem\FilterFileInfo');

        $fileInfo->getSize()->willThrow(new UnsupportedFilterException());

        (new SizeSmallerThan(512))->isSatisfiedBy($fileInfo->reveal());
    }
}
