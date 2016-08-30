<?php

namespace League\Flysystem\Filter;

class NotXTests extends \PHPUnit_Framework_TestCase
{
    public function testWorksAsLogicNotOfCriteriaConstructedWith()
    {
        $filterCriteriaOne = $this->prophesize('League\Flysystem\FilterCriteriaInterface');
        $filterCriteriaTwo = $this->prophesize('League\Flysystem\FilterCriteriaInterface');
        $filterFileInfo = $this->prophesize('League\Flysystem\FilterFileInfo');

        $filterCriteriaOne->isSatisfiedBy($filterFileInfo)->willReturn(true);
        $filterCriteriaTwo->isSatisfiedBy($filterFileInfo)->willReturn(false);

        $this->assertFalse((new NotX($filterCriteriaOne->reveal()))
            ->isSatisfiedBy($filterFileInfo->reveal()));
        $this->assertTrue((new NotX($filterCriteriaTwo->reveal()))
            ->isSatisfiedBy($filterFileInfo->reveal()));
    }
}
