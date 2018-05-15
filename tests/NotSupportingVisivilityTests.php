<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Stub\NotSupportingVisibilityStub;
use PHPUnit\Framework\TestCase;

class NotSupportingVisivilityTests extends TestCase
{

    public function testGetVisibility()
    {
        $this->expectException('LogicException');
        $stub = new NotSupportingVisibilityStub();
        $stub->getVisibility('path.txt');
    }

    public function testSetVisibility()
    {
        $this->expectException('LogicException');
        $stub = new NotSupportingVisibilityStub();
        $stub->setVisibility('path.txt', 'public');
    }
}
