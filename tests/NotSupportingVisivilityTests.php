<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Stub\NotSupportingVisibilityStub;

class NotSupportingVisivilityTests extends \PHPUnit_Framework_TestCase
{
    public function testGetVisibility()
    {
        $stub = new NotSupportingVisibilityStub();
        $result = $stub->getVisibility('path.txt');
        $this->assertSame(['visibility' => AdapterInterface::VISIBILITY_PUBLIC], $result);
    }

    public function testSetVisibility()
    {
        $stub = new NotSupportingVisibilityStub();

        $result = $stub->setVisibility('path.txt', AdapterInterface::VISIBILITY_PUBLIC);
        $this->assertSame(['visibility' => AdapterInterface::VISIBILITY_PUBLIC], $result);

        // Setting to private is un-supported.
        $this->assertFalse($stub->setVisibility('path.txt', AdapterInterface::VISIBILITY_PRIVATE));
    }
}
