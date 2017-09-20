<?php

namespace League\Flysystem\Filter;

use League\Flysystem\UnsupportedFilterException;

class ExtensionEqualsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAllowCreationWithStringOnly()
    {
        new ExtensionEquals(2323);
    }

    public function testRecognizeExtensionOfFile()
    {
        $fileInfoTxt = $this->prophesize('League\Flysystem\FilterFileInfo');
        $fileInfoBmp = $this->prophesize('League\Flysystem\FilterFileInfo');

        $fileInfoTxt->getExtension()->willReturn('txt');
        $fileInfoBmp->getExtension()->willReturn('bmp');

        $this->assertFalse(
            (new ExtensionEquals('bmp'))->isSatisfiedBy($fileInfoTxt->reveal())
        );
        $this->assertTrue(
            (new ExtensionEquals('bmp'))->isSatisfiedBy($fileInfoBmp->reveal())
        );
    }

    /**
     * @expectedException League\Flysystem\UnsupportedFilterException
     */
    public function testWillNotFilterWhenExtensionIsNotSupportedByFileInfo()
    {
        $fileInfo = $this->prophesize('League\Flysystem\FilterFileInfo');

        $fileInfo->getExtension()->willThrow(new UnsupportedFilterException());

        (new ExtensionEquals('bmp'))->isSatisfiedBy($fileInfo->reveal());
    }
}
