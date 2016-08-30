<?php

namespace League\Flysystem\Filter;

use League\Flysystem\UnsupportedFilterException;

class FilenameStartsWithTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAllowCreationWithStringOnly()
    {
        new FilenameStartsWith(2323);
    }

    public function testPickFilesWithEqualFilename()
    {
        $fileInfoSameName = $this->prophesize('League\Flysystem\FilterFileInfo');
        $fileInfoOtherName = $this->prophesize('League\Flysystem\FilterFileInfo');

        $fileInfoSameName->getFilename()->willReturn('file');
        $fileInfoOtherName->getFilename()->willReturn('other_file');

        $this->assertTrue(
            (new FilenameStartsWith('fil'))->isSatisfiedBy($fileInfoSameName->reveal())
        );
        $this->assertFalse(
            (new FilenameStartsWith('fil'))->isSatisfiedBy($fileInfoOtherName->reveal())
        );
    }

    /**
     * @expectedException League\Flysystem\UnsupportedFilterException
     */
    public function testWillNotFilterWhenFilenameIsNotSupportedByFileInfo()
    {
        $fileInfo = $this->prophesize('League\Flysystem\FilterFileInfo');

        $fileInfo->getFilename()->willThrow(new UnsupportedFilterException());

        (new FilenameStartsWith('fil'))->isSatisfiedBy($fileInfo->reveal());
    }
}
