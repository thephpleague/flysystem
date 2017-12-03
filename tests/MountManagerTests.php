<?php

use League\Flysystem\MountManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class MountManagerTests extends TestCase
{
    use \PHPUnitHacks;

    public function testInstantiable()
    {
        new MountManager();
    }

    public function testConstructorInjection()
    {
        $mock = $this->createMock('League\Flysystem\FilesystemInterface');
        $manager = new MountManager([
            'prefix' => $mock,
        ]);
        $this->assertEquals($mock, $manager->getFilesystem('prefix'));
    }

    /**
     * @expectedException  InvalidArgumentException
     */
    public function testInvalidPrefix()
    {
        $manager = new MountManager();
        $manager->mountFilesystem(false, $this->createMock('League\Flysystem\FilesystemInterface'));
    }

    /**
     * @expectedException  LogicException
     */
    public function testUndefinedFilesystem()
    {
        $manager = new MountManager();
        $manager->getFilesystem('prefix');
    }

    public function invalidCallProvider()
    {
        return [
            [[], 'LogicException'],
            [[false], 'InvalidArgumentException'],
            [['path/without/protocol'], 'InvalidArgumentException'],
        ];
    }

    /**
     * @dataProvider  invalidCallProvider
     */
    public function testInvalidArguments($arguments, $exception)
    {
        $this->expectException($exception);
        $manager = new MountManager();
        $manager->filterPrefix($arguments);
    }

    public function testCallForwarder()
    {
        $manager = new MountManager();
        $mock = $this->getMockBuilder('League\Flysystem\Filesystem')->disableOriginalConstructor()->getMock();
        $mock->expects($this->once())
            ->method('__call')
            ->with('aMethodCall', ['file.ext'])
            ->willReturn('a result');
        $manager->mountFilesystem('prot', $mock);
        $this->assertEquals($manager->aMethodCall('prot://file.ext'), 'a result');
    }

    public function testCopyBetweenFilesystems()
    {
        $manager = new MountManager();
        $fs1 = $this->prophesize('League\Flysystem\FilesystemInterface');
        $fs2 = $this->prophesize('League\Flysystem\FilesystemInterface');
        $manager->mountFilesystem('fs1', $fs1->reveal());
        $manager->mountFilesystem('fs2', $fs2->reveal());

        $filename = 'test1.txt';
        $buffer = tmpfile();
        $fs1->readStream($filename)->willReturn($buffer)->shouldBeCalledTimes(1);
        $fs2->writeStream($filename, $buffer, [])->willReturn(true)->shouldBeCalledTimes(1);
        $response = $manager->copy("fs1://{$filename}", "fs2://{$filename}");
        $this->assertTrue($response);

        // test failed status
        $filename = 'test2.txt';
        $fs1->readStream($filename)->willReturn(false)->shouldBeCalledTimes(1);
        $status = $manager->copy("fs1://{$filename}", "fs2://{$filename}");
        $this->assertFalse($status);

        $filename = 'test3.txt';
        $fs1->readStream($filename)->willReturn($buffer)->shouldBeCalledTimes(1);
        $fs2->writeStream($filename, $buffer, [])->willReturn(false)->shouldBeCalledTimes(1);
        $status = $manager->copy("fs1://{$filename}", "fs2://{$filename}");
        $this->assertFalse($status);

        $filename = 'test4.txt';
        $fs1->readStream($filename)->willReturn($buffer)->shouldBeCalledTimes(1);
        $fs2->writeStream($filename, $buffer, [])->willReturn(true)->shouldBeCalledTimes(1);
        $status = $manager->copy("fs1://{$filename}", "fs2://{$filename}");
        $this->assertTrue($status);
    }

    public function testMoveBetweenFilesystems()
    {
        $manager = $this->getMockBuilder('League\Flysystem\MountManager')
            ->setMethods(['copy', 'delete'])
            ->getMock();
        $fs1 = $this->prophesize('League\Flysystem\FilesystemInterface');
        $fs2 = $this->prophesize('League\Flysystem\FilesystemInterface');
        $manager->mountFilesystem('fs1', $fs1->reveal());
        $manager->mountFilesystem('fs2', $fs2->reveal());

        $filename = 'test.txt';
        $buffer = tmpfile();
        $fs1->readStream($filename)->willReturn($buffer);
        $fs2->writeStream($filename, $buffer, [])->willReturn(false);
        $code = $manager->move("fs1://{$filename}", "fs2://{$filename}");
        $this->assertFalse($code);

        $manager->method('copy')->with("fs1://{$filename}", "fs2://{$filename}", [])->willReturn(true);
        $manager->method('delete')->with("fs1://{$filename}")->willReturn(true);
        $code = $manager->move("fs1://{$filename}", "fs2://{$filename}");

        $this->assertTrue($code);
    }

    public function testMoveSameFilesystems()
    {
        $manager = new MountManager();
        $fs = $this->prophesize('League\Flysystem\FilesystemInterface');
        $manager->mountFilesystem('fs1', $fs->reveal());

        $config = ['visibility' => 'private'];
        $fs->rename('old.txt', 'new.txt')->willReturn(true);
        $fs->setVisibility('new.txt', 'private')->willReturn(true);

        $this->assertTrue($manager->move('fs1://old.txt', 'fs1://new.txt'));
        $this->assertTrue($manager->move('fs1://old.txt', 'fs1://new.txt', $config));
    }

    protected function mockFilesystem()
    {
        $mock = $this->prophesize('League\Flysystem\FilesystemInterface');
        $mock->listContents(Argument::type('string'), false)->willReturn([
           ['path' => 'path.txt', 'type' => 'file'],
           ['path' => 'dirname/path.txt', 'type' => 'file'],
        ]);

        return $mock->reveal();
    }

    public function testFileWithAliasWithMountManager()
    {
        $fs = $this->mockFilesystem();
        $fs2 = $this->mockFilesystem();

        $mountManager = new MountManager();
        $mountManager->mountFilesystem('local', $fs);
        $mountManager->mountFilesystem('huge', $fs2);
        $results = $mountManager->listContents("local://tests/files");

        foreach ($results as $result) {
            $this->assertArrayHasKey('filesystem', $result);
            $this->assertEquals($result['filesystem'], 'local');
        }

        $results = $mountManager->listContents("huge://tests/files");
        foreach ($results as $result) {
            $this->assertArrayHasKey('filesystem', $result);
            $this->assertEquals($result['filesystem'], 'huge');
        }
    }

    public function testListWith()
    {
        $manager = new MountManager();
        $response = ['path' => 'file.ext', 'timestamp' => time()];
        $mock = $this->getMockBuilder('League\Flysystem\Filesystem')->disableOriginalConstructor()->getMock();
        $mock->method('__call')->with('listWith', [['timestamp'], 'file.ext', false])->willReturn($response);
        $manager->mountFilesystem('prot', $mock);
        $this->assertEquals($response, $manager->listWith(['timestamp'], 'prot://file.ext', false));
    }

    public function provideMountSchemas()
    {
        return [['with.dot'], ['with-dash'], ['with+plus'], ['with:colon']];
    }

    /**
     * @dataProvider provideMountSchemas
     */
    public function testMountSchemaTypes($schema)
    {
        $manager = new MountManager();
        $mock = $this->getMockBuilder('League\Flysystem\Filesystem')->disableOriginalConstructor()->getMock();
        $mock->method('__call')->with('aMethodCall', ['file.ext'])->willReturn('a result');
        $manager->mountFilesystem($schema, $mock);
        $this->assertEquals($manager->aMethodCall($schema . '://file.ext'), 'a result');
    }
}
