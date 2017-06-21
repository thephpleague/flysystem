<?php

namespace League\Flysystem;

use League\Flysystem\SafeStorage;

class SafeStorageTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAndSet()
    {
        $a = new SafeStorage();
        $a->storeSafely('a_key', 'a_value');
        $this->assertSame('a_value', $a->retrieveSafely('a_key'));

        $b = new SafeStorage();
        $b->storeSafely('b_key', 'b_value');
        $this->assertSame('b_value', $b->retrieveSafely('b_key'));

        // Check that storage does not leak between instances.
        $this->assertNull($b->retrieveSafely('a_key'));
        $this->assertNull($a->retrieveSafely('b_key'));
    }

    /**
     * @expectedException \LogicException
     */
    public function testSerialize()
    {
        serialize(new SafeStorage());
    }
}
