<?php

namespace League\Flysystem\Filter;

use League\Flysystem\UnsupportedFilterException;

class IsPublicTest extends \PHPUnit_Framework_TestCase
{
    public function testChecksPublicVisibilityOfFile()
    {
        $fileInfoPublic = $this->prophesize('League\Flysystem\FilterFileInfo');
        $fileInfoPrivate = $this->prophesize('League\Flysystem\FilterFileInfo');

        $fileInfoPublic->getVisibility()->willReturn('public');
        $fileInfoPrivate->getVisibility()->willReturn('private');

        $this->assertTrue(
            (new IsPublic())->isSatisfiedBy($fileInfoPublic->reveal())
        );
        $this->assertFalse(
            (new IsPublic())->isSatisfiedBy($fileInfoPrivate->reveal())
        );
    }

    /**
     * @expectedException League\Flysystem\UnsupportedFilterException
     */
    public function testWillNotFilterWhenVislibilityIsNotSupportedByFileInfo()
    {
        $fileInfo = $this->prophesize('League\Flysystem\FilterFileInfo');

        $fileInfo->getVisibility()->willThrow(new UnsupportedFilterException());

        (new IsPublic())->isSatisfiedBy($fileInfo->reveal());
    }
}
