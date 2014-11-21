<?php

use League\Flysystem\MountManager;

class MountManagerTests extends PHPUnit_Framework_TestCase
{
    public function testInstantiable()
    {
        $manager = new MountManager;
    }

    public function testConstructorInjection()
    {
        $mock = Mockery::mock('League\Flysystem\FilesystemInterface');
        $manager = new MountManager(array(
            'prefix' => $mock,
        ));
        $this->assertEquals($mock, $manager->getFilesystem('prefix'));
    }

    /**
     * @expectedException  InvalidArgumentException
     */
    public function testInvalidPrefix()
    {
        $manager = new MountManager;
        $manager->mountFilesystem(false, Mockery::mock('League\Flysystem\FilesystemInterface'));
    }

    /**
     * @expectedException  LogicException
     */
    public function testUndefinedFilesystem()
    {
        $manager = new MountManager;
        $manager->getFilesystem('prefix');
    }

    public function invalidCallProvider()
    {
        return array(
            array(array(), 'LogicException'),
            array(array(false), 'InvalidArgumentException'),
            array(array('path/without/protocol'), 'InvalidArgumentException'),
        );
    }

    /**
     * @dataProvider  invalidCallProvider
     */
    public function testInvalidArguments($arguments, $exception)
    {
        $this->setExpectedException($exception);
        $manager = new MountManager;
        $manager->filterPrefix($arguments);
    }

    public function testCallForwarder()
    {
        $manager = new MountManager;
        $mock = Mockery::mock('League\Flysystem\FilesystemInterface');
        $mock->shouldReceive('aMethodCall')->once()->andReturn('a result');
        $manager->mountFilesystem('prot', $mock);
        $this->assertEquals($manager->aMethodCall('prot://file.ext'), 'a result');
    }

    public function testCopyBetweenFilesystems()
    {
        $manager = new MountManager;
        $fs1 = Mockery::mock('League\Flysystem\FilesystemInterface');
        $fs2 = Mockery::mock('League\Flysystem\FilesystemInterface');
        $manager->mountFilesystem('fs1', $fs1);
        $manager->mountFilesystem('fs2', $fs2);

        $filename = 'test.txt';
        $buffer = 'a string inside filename';
        $fs1->shouldReceive('read')->once()->with($filename)->andReturn($buffer);
        $fs2->shouldReceive('write')->once()->with($filename, $buffer)->andReturn(true);

        $manager->copy("fs1://{$filename}", "fs2://{$filename}");

        // test failed status
        $fs1->shouldReceive('read')->once()->with($filename)->andReturn(false);

        $status = $manager->copy("fs1://{$filename}", "fs2://{$filename}");
        $this->assertFalse($status);

        $fs1->shouldReceive('read')->once()->with($filename)->andReturn($buffer);
        $fs2->shouldReceive('write')->once()->with($filename, $buffer)->andReturn(false);

        $status = $manager->copy("fs1://{$filename}", "fs2://{$filename}");
        $this->assertFalse($status);

        $fs1->shouldReceive('read')->once()->with($filename)->andReturn($buffer);
        $fs2->shouldReceive('write')->once()->with($filename, $buffer)->andReturn(true);

        $status = $manager->copy("fs1://{$filename}", "fs2://{$filename}");
        $this->assertTrue($status);
    }

    public function testMoveBetweenFilesystems()
    {
        $manager = Mockery::mock('League\Flysystem\MountManager')->makePartial();
        $fs1 = Mockery::mock('League\Flysystem\FilesystemInterface');
        $fs2 = Mockery::mock('League\Flysystem\FilesystemInterface');
        $manager->mountFilesystem('fs1', $fs1);
        $manager->mountFilesystem('fs2', $fs2);

        $filename = 'test.txt';
        $buffer = 'a string inside filename';
        $fs1->shouldReceive('read')->with($filename)->andReturn($buffer);
        $fs2->shouldReceive('write')->with($filename, $buffer)->andReturn(false);
        $code = $manager->move("fs1://{$filename}", "fs2://{$filename}");
        $this->assertFalse($code);


        $manager->shouldReceive('copy')->with("fs1://{$filename}", "fs2://{$filename}")->andReturn(true);
        $manager->shouldReceive('delete')->with("fs1://{$filename}")->andReturn(true);
        $code = $manager->move("fs1://{$filename}", "fs2://{$filename}");

        $this->assertTrue($code);
    }
}
