<?php

namespace League\Flysystem\Filter;

class OrXTests extends \PHPUnit_Framework_TestCase
{
    public function testConstructionWithDifferentNumberOfArguments()
    {
        $filterCriteriaOne = $this->prophesize('League\Flysystem\FilterCriteriaInterface');
        $filterCriteriaTwo = $this->prophesize('League\Flysystem\FilterCriteriaInterface');
        $filterCriteriaThree = $this->prophesize('League\Flysystem\FilterCriteriaInterface');

        new OrX($filterCriteriaOne->reveal());
        new OrX($filterCriteriaOne->reveal(), $filterCriteriaTwo->reveal());
        new OrX($filterCriteriaOne->reveal(), $filterCriteriaThree->reveal(), $filterCriteriaTwo->reveal());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCanBeConstructedOnlyWithFilterCriterias()
    {
        $filterCriteria = $this->prophesize('League\Flysystem\FilterCriteriaInterface');
        $string = 'string';

        new OrX($filterCriteria->reveal(), $string);
    }

    public function testWorksAsLogicOrOfCriteriasConstructedWith()
    {
        $filterCriteriaOne = $this->prophesize('League\Flysystem\FilterCriteriaInterface');
        $filterCriteriaTwo = $this->prophesize('League\Flysystem\FilterCriteriaInterface');
        $filterFileInfo = $this->prophesize('League\Flysystem\FilterFileInfo');

        $filterCriteriaOne->isSatisfiedBy($filterFileInfo)->willReturn(true);
        $filterCriteriaTwo->isSatisfiedBy($filterFileInfo)->willReturn(false);

        $this->assertTrue((new OrX($filterCriteriaOne->reveal(), $filterCriteriaOne->reveal()))
            ->isSatisfiedBy($filterFileInfo->reveal()));
        $this->assertTrue((new OrX($filterCriteriaOne->reveal(), $filterCriteriaTwo->reveal()))
            ->isSatisfiedBy($filterFileInfo->reveal()));
        $this->assertFalse((new OrX($filterCriteriaTwo->reveal(), $filterCriteriaTwo->reveal()))
            ->isSatisfiedBy($filterFileInfo->reveal()));
    }
}
