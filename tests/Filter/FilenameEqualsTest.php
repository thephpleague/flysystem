<?php

namespace League\Flysystem\Filter;

use League\Flysystem\UnsupportedFilterException;

class FilenameEqualsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAllowCreationWithStringOnly()
    {
        new FilenameEquals(2323);
    }

    public function testPickFilesWithEqualFilename()
    {
        $fileInfoSameName = $this->prophesize('League\Flysystem\FilterFileInfo');
        $fileInfoOtherName = $this->prophesize('League\Flysystem\FilterFileInfo');

        $fileInfoSameName->getFilename()->willReturn('file');
        $fileInfoOtherName->getFilename()->willReturn('other_file');

        $this->assertTrue(
            (new FilenameEquals('file'))->isSatisfiedBy($fileInfoSameName->reveal())
        );
        $this->assertFalse(
            (new FilenameEquals('file'))->isSatisfiedBy($fileInfoOtherName->reveal())
        );
    }

    /**
     * @expectedException League\Flysystem\UnsupportedFilterException
     */
    public function testWillNotFilterWhenFilenameIsNotSupportedByFileInfo()
    {
        $fileInfo = $this->prophesize('League\Flysystem\FilterFileInfo');

        $fileInfo->getFilename()->willThrow(new UnsupportedFilterException());

        (new FilenameEquals('file'))->isSatisfiedBy($fileInfo->reveal());
    }
}
