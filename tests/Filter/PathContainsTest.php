<?php

namespace League\Flysystem\Filter;

use League\Flysystem\UnsupportedFilterException;

class PathContainsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAllowCreationWithStringOnly()
    {
        new PathContains(2323);
    }

    public function testPickFilesWithEqualFilename()
    {
        $fileInfoSameName = $this->prophesize('League\Flysystem\FilterFileInfo');
        $fileInfoSameWithDirName = $this->prophesize('League\Flysystem\FilterFileInfo');
        $fileInfoOtherName = $this->prophesize('League\Flysystem\FilterFileInfo');

        $fileInfoSameName->getPath()->willReturn('file.txt');
        $fileInfoSameWithDirName->getPath()->willReturn('dir/file.txt');
        $fileInfoOtherName->getPath()->willReturn('dir1/dir2/other.txt');

        $this->assertTrue(
            (new PathContains('fil'))->isSatisfiedBy($fileInfoSameName->reveal())
        );
        $this->assertTrue(
            (new PathContains('fil'))->isSatisfiedBy($fileInfoSameWithDirName->reveal())
        );
        $this->assertFalse(
            (new PathContains('fil'))->isSatisfiedBy($fileInfoOtherName->reveal())
        );
    }

    /**
     * @expectedException League\Flysystem\UnsupportedFilterException
     */
    public function testWillNotFilterWhenPathIsNotSupportedByFileInfo()
    {
        $fileInfo = $this->prophesize('League\Flysystem\FilterFileInfo');

        $fileInfo->getPath()->willThrow(new UnsupportedFilterException());

        (new PathContains('fil'))->isSatisfiedBy($fileInfo->reveal());
    }
}
