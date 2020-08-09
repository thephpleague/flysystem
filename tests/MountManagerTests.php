<?php

use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use League\Flysystem\Plugin\ListWith;
use League\Flysystem\Stub\FilesystemSpy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class MountManagerTests extends TestCase
{

    public function testInstantiable()
    {
        $instance = new MountManager();
        $this->assertInstanceOf(MountManager::class, $instance);
    }

    public function testConstructorInjection()
    {
        $mock = $this->prophesize(FilesystemInterface::class)->reveal();
        $manager = new MountManager([
            'prefix' => $mock,
        ]);
        $this->assertEquals($mock, $manager->getFilesystem('prefix'));
    }

    public function testInvalidPrefix()
    {
        $this->expectException(InvalidArgumentException::class);
        $filesystem = $this->prophesize(FilesystemInterface::class)->reveal();
        $manager = new MountManager();
        $manager->mountFilesystem(false, $filesystem);
    }

    public function testUndefinedFilesystem()
    {
        $this->expectException(LogicException::class);
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

    public function testMoveBetweenFilesystemsCanFail()
    {
        $manager = new MountManager();
        $fs1 = $this->prophesize('League\Flysystem\FilesystemInterface');
        $fs2 = $this->prophesize('League\Flysystem\FilesystemInterface');
        $manager->mountFilesystem('fs1', $fs1->reveal());
        $manager->mountFilesystem('fs2', $fs2->reveal());

        $filename = 'test.txt';
        $buffer = tmpfile();
        $fs1->readStream($filename)->willReturn($buffer);
        $fs2->writeStream($filename, $buffer, [])->willReturn(false);
        $result = $manager->move("fs1://{$filename}", "fs2://{$filename}");
        $this->assertFalse($result);
    }

    public function testMoveBetweenFilesystemsCanSucceed()
    {
        $manager = new MountManager();
        $fs1 = $this->prophesize('League\Flysystem\FilesystemInterface');
        $fs2 = $this->prophesize('League\Flysystem\FilesystemInterface');
        $manager->mountFilesystem('fs1', $fs1->reveal());
        $manager->mountFilesystem('fs2', $fs2->reveal());

        $filename = 'test.txt';
        $buffer = tmpfile();
        $fs1->readStream($filename)->willReturn($buffer);
        $fs2->writeStream($filename, $buffer, [])->willReturn(true);
        $fs1->delete($filename)->willReturn(true);
        $result = $manager->move("fs1://{$filename}", "fs2://{$filename}");
        $this->assertTrue($result);
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
        $fs = new Filesystem(new Local(__DIR__ . '/files'));
        $fs->deleteDir('dirname');
        $fs->addPlugin(new ListWith());
        $fs->write('dirname/file.txt', 'contents');
        $listing = $fs->listWith(['timestamp'], 'dirname', false);
        $manager->mountFilesystem('prot', $fs);
        $this->assertEquals($listing, $manager->listWith(['timestamp'], 'prot://dirname', false));
        $fs->deleteDir('dirname');
    }

    public function provideMountSchemas()
    {
        return [
            ['with.dot'],
            ['with-dash'],
            ['with+plus'],
            ['with:colon']
        ];
    }

    /**
     * @dataProvider provideMountSchemas
     */
    public function testMountSchemaTypes($schema)
    {
        $manager = new MountManager();
        $mock = $this->prophesize(FilesystemInterface::class);
        $mock->read('file.ext')->willReturn('a result');
        $manager->mountFilesystem($schema, $mock->reveal());
        $this->assertEquals($manager->read($schema . '://file.ext'), 'a result');
    }

    /**
     * @dataProvider methodForwardingProvider
     */
    public function testMethodForwarding($method, array $arguments)
    {
        $mountManager = new MountManager();
        $filesystem = new FilesystemSpy();
        $mountManager->mountFilesystem('local', $filesystem);
        $expectedCall = FilesystemSpy::class . '::' . $method;
        $callingArguments = $arguments;
        $callingArguments[0] = "local://{$callingArguments[0]}";
        call_user_func_array([$mountManager, $method], $callingArguments);

        $this->assertEquals([$expectedCall, $arguments], $filesystem->lastCall);
    }

    public function methodForwardingProvider()
    {
        return [
            ['write', ['path.txt', 'contents', []]],
            ['writeStream', ['path.txt', 'contents', []]],
            ['update', ['path.txt', 'contents', []]],
            ['updateStream', ['path.txt', 'contents', []]],
            ['put', ['path.txt', 'contents', []]],
            ['putStream', ['path.txt', 'contents', []]],
            ['read', ['path.txt']],
            ['readStream', ['path.txt']],
            ['readAndDelete', ['path.txt']],
            ['get', ['path.txt']],
            ['has', ['path.txt']],
            ['getMetadata', ['path.txt']],
            ['getMimetype', ['path.txt']],
            ['getTimestamp', ['path.txt']],
            ['getSize', ['path.txt']],
            ['delete', ['path.txt']],
            ['deleteDir', ['dirname']],
            ['createDir', ['dirname']],
            ['rename', ['name', 'other-name']],
            ['setVisibility', ['name', AdapterInterface::VISIBILITY_PUBLIC]],
            ['getVisibility', ['name']],
        ];
    }
}
