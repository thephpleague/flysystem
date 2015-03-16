<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Stub\NotSupportingVisibilityStub;

class NotSupportingVisivilityTests extends \PHPUnit_Framework_TestCase
{
    public function testGetVisibility()
    {
        $this->setExpectedException('LogicException');
        $stub = new NotSupportingVisibilityStub();
        $stub->getVisibility('path.txt');
    }

    public function testSetVisibility()
    {
        $this->setExpectedException('LogicException');
        $stub = new NotSupportingVisibilityStub();
        $stub->setVisibility('path.txt', 'public');
    }
}
