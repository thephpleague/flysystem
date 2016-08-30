<?php

namespace League\Flysystem\Filter;

class AndXTests extends \PHPUnit_Framework_TestCase
{
    public function testConstructionWithDifferentNumberOfArguments()
    {
        $filterCriteriaOne = $this->prophesize('League\Flysystem\FilterCriteriaInterface');
        $filterCriteriaTwo = $this->prophesize('League\Flysystem\FilterCriteriaInterface');
        $filterCriteriaThree = $this->prophesize('League\Flysystem\FilterCriteriaInterface');

        new AndX($filterCriteriaOne->reveal());
        new AndX($filterCriteriaOne->reveal(), $filterCriteriaTwo->reveal());
        new AndX($filterCriteriaOne->reveal(), $filterCriteriaThree->reveal(), $filterCriteriaTwo->reveal());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCanBeConstructedOnlyWithFilterCriterias()
    {
        $filterCriteria = $this->prophesize('League\Flysystem\FilterCriteriaInterface');
        $string = 'string';

        new AndX($filterCriteria->reveal(), $string);
    }

    public function testWorksAsLogicAndOfCriteriasConstructedWith()
    {
        $filterCriteriaOne = $this->prophesize('League\Flysystem\FilterCriteriaInterface');
        $filterCriteriaTwo = $this->prophesize('League\Flysystem\FilterCriteriaInterface');
        $filterFileInfo = $this->prophesize('League\Flysystem\FilterFileInfo');

        $filterCriteriaOne->isSatisfiedBy($filterFileInfo)->willReturn(true);
        $filterCriteriaTwo->isSatisfiedBy($filterFileInfo)->willReturn(false);

        $this->assertTrue((new AndX($filterCriteriaOne->reveal(), $filterCriteriaOne->reveal()))
            ->isSatisfiedBy($filterFileInfo->reveal()));
        $this->assertFalse((new AndX($filterCriteriaOne->reveal(), $filterCriteriaTwo->reveal()))
            ->isSatisfiedBy($filterFileInfo->reveal()));
        $this->assertFalse((new AndX($filterCriteriaTwo->reveal(), $filterCriteriaTwo->reveal()))
            ->isSatisfiedBy($filterFileInfo->reveal()));
    }
}
