<?php

namespace League\Flysystem\Filter;

use League\Flysystem\UnsupportedFilterException;

class IsFileTest extends \PHPUnit_Framework_TestCase
{
    public function testChecksIfIsFile()
    {
        $fileInfoFile = $this->prophesize('League\Flysystem\FilterFileInfo');
        $fileInfoDirectory = $this->prophesize('League\Flysystem\FilterFileInfo');

        $fileInfoFile->getType()->willReturn('file');
        $fileInfoDirectory->getType()->willReturn('directory');

        $this->assertTrue(
            (new IsFile())->isSatisfiedBy($fileInfoFile->reveal())
        );
        $this->assertFalse(
            (new IsFile())->isSatisfiedBy($fileInfoDirectory->reveal())
        );
    }

    /**
     * @expectedException League\Flysystem\UnsupportedFilterException
     */
    public function testWillNotFilterWhenTypeIsNotSupportedByFileInfo()
    {
        $fileInfo = $this->prophesize('League\Flysystem\FilterFileInfo');

        $fileInfo->getType()->willThrow(new UnsupportedFilterException());

        (new IsFile())->isSatisfiedBy($fileInfo->reveal());
    }
}
