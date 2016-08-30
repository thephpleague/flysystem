<?php

namespace League\Flysystem\Filter;

use League\Flysystem\UnsupportedFilterException;

class IsPrivateTest extends \PHPUnit_Framework_TestCase
{
    public function testChecksPrivateVisibilityOfFile()
    {
        $fileInfoPublic = $this->prophesize('League\Flysystem\FilterFileInfo');
        $fileInfoPrivate = $this->prophesize('League\Flysystem\FilterFileInfo');

        $fileInfoPublic->getVisibility()->willReturn('public');
        $fileInfoPrivate->getVisibility()->willReturn('private');

        $this->assertFalse(
            (new IsPrivate())->isSatisfiedBy($fileInfoPublic->reveal())
        );
        $this->assertTrue(
            (new IsPrivate())->isSatisfiedBy($fileInfoPrivate->reveal())
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
