<?php

namespace League\Flysystem\Filter;

use League\Flysystem\UnsupportedFilterException;

class IsDirectoryTest extends \PHPUnit_Framework_TestCase
{
    public function testChecksIfIsFile()
    {
        $fileInfoFile = $this->prophesize('League\Flysystem\FilterFileInfo');
        $fileInfoDirectory = $this->prophesize('League\Flysystem\FilterFileInfo');

        $fileInfoFile->getType()->willReturn('file');
        $fileInfoDirectory->getType()->willReturn('dir');

        $this->assertFalse(
            (new IsDirectory())->isSatisfiedBy($fileInfoFile->reveal())
        );
        $this->assertTrue(
            (new IsDirectory())->isSatisfiedBy($fileInfoDirectory->reveal())
        );
    }

    /**
     * @expectedException League\Flysystem\UnsupportedFilterException
     */
    public function testWillNotFilterWhenTypeIsNotSupportedByFileInfo()
    {
        $fileInfo = $this->prophesize('League\Flysystem\FilterFileInfo');

        $fileInfo->getType()->willThrow(new UnsupportedFilterException());

        (new IsDirectory())->isSatisfiedBy($fileInfo->reveal());
    }
}
