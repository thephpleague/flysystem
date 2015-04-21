<?php

use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;

class MountManagerTests extends PHPUnit_Framework_TestCase
{
    public function testInstantiable()
    {
        $manager = new MountManager();
    }

    public function testConstructorInjection()
    {
        $mock = Mockery::mock('League\Flysystem\FilesystemInterface');
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
        $manager->mountFilesystem(false, Mockery::mock('League\Flysystem\FilesystemInterface'));
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
        $this->setExpectedException($exception);
        $manager = new MountManager();
        $manager->filterPrefix($arguments);
    }

    /**
     * @dataProvider  validPrefixProvider
     */
    public function testValidPrefixes($prefix, $arguments)
    {
        $manager = new MountManager();
        $fs = Mockery::mock('League\Flysystem\FilesystemInterface');
        $manager->mountFilesystem($prefix, $fs);
        $result = $manager->filterPrefix($arguments);
        $this->assertEquals($prefix, $result[0], '"' . $prefix . '" should be a valid scheme/prefix');
    }

    public function validPrefixProvider()
    {
        return [
            [ 'https', ['https://asdf'] ],
            [ 'user-assets', ['user-assets://some/path.jpg'] ],
            [ 'article-images', ['article-images://some/path.jpg'] ],
            [ 'chrome-extension', ['chrome-extension://index.js'] ],
            [ 'ftp+ssl', ['ftp+ssl://some/file.ext'] ],
            [ 'svn+ssh', ['svn+ssh://some/pic.png'] ],
            [ 'web+auth', ['web+auth://some/other/image'] ],
            [ 'cdn.images', ['cdn.images://category.images.jpg'] ],
        ];
    }

    /**
     * @dataProvider  invalidPrefixProvider
     */
    public function testInvalidPrefixes($prefix, $arguments, $exception)
    {
        $this->setExpectedException($exception);
        $manager = new MountManager();
        $fs = Mockery::mock('League\Flysystem\FilesystemInterface');
        $manager->mountFilesystem($prefix, $fs);
        $result = $manager->filterPrefix($arguments);
    }

    public function invalidPrefixProvider()
    {
        return [
            [ '1up', ['1up://asdf'], 'InvalidArgumentException' ],
            [ '1337', ['1337://asdf'], 'InvalidArgumentException' ],
            [ '-foo', ['-foo://asdf'], 'InvalidArgumentException' ],
            [ '+bar', ['+bar://asdf'], 'InvalidArgumentException' ],
            [ '.asdf', ['.asdf://asdf'], 'InvalidArgumentException' ],
            [ '.', ['.://asdf'], 'InvalidArgumentException' ],
            [ '.http', ['.http://asdf'], 'InvalidArgumentException' ],
            [ '+', ['+://asdf'], 'InvalidArgumentException' ],
            [ '+http', ['+http://asdf'], 'InvalidArgumentException' ],
            [ '-', ['.asdf://asdf'], 'InvalidArgumentException' ],
            [ '-http', ['-http://asdf'], 'InvalidArgumentException' ],
            [ '/', ['/://asdf'], 'InvalidArgumentException' ],
            [ '/http', ['/http://asdf'], 'InvalidArgumentException' ],
            [ ':', [':://asdf'], 'InvalidArgumentException' ],
            [ ':http', [':http://asdf'], 'InvalidArgumentException' ],
            [ '://', ['://://asdf'], 'InvalidArgumentException' ],
        ];
    }

    public function testMountFilesystemFailsWithInvalidPrefixGiven()
    {
        $this->setExpectedException('InvalidArgumentException');
        $manager = new MountManager();
        $fs = Mockery::mock('League\Flysystem\FilesystemInterface');
        $manager->mountFilesystem('+invalid', $fs);
    }

    public function testCaseInsensitivePrefixUsageWorks()
    {
        $manager = new MountManager();
        $fs = Mockery::mock('League\Flysystem\FilesystemInterface');
        $fs->shouldReceive('aMethodCall')->once()->andReturn('a result');
        $manager->mountFilesystem('ASSETS', $fs);
        $this->assertEquals($manager->aMethodCall('assets://file.ext'), 'a result');
    }

    public function testCallForwarder()
    {
        $manager = new MountManager();
        $mock = Mockery::mock('League\Flysystem\FilesystemInterface');
        $mock->shouldReceive('aMethodCall')->once()->andReturn('a result');
        $manager->mountFilesystem('prot', $mock);
        $this->assertEquals($manager->aMethodCall('prot://file.ext'), 'a result');
    }

    public function testCopyBetweenFilesystems()
    {
        $manager = new MountManager();
        $fs1 = Mockery::mock('League\Flysystem\FilesystemInterface');
        $fs2 = Mockery::mock('League\Flysystem\FilesystemInterface');
        $manager->mountFilesystem('fs1', $fs1);
        $manager->mountFilesystem('fs2', $fs2);

        $filename = 'test.txt';
        $buffer = tmpfile();
        $fs1->shouldReceive('readStream')->once()->with($filename)->andReturn($buffer);
        $fs2->shouldReceive('writeStream')->once()->with($filename, $buffer)->andReturn(true);
        $response = $manager->copy("fs1://{$filename}", "fs2://{$filename}");
        $this->assertTrue($response);

        // test failed status
        $fs1->shouldReceive('readStream')->once()->with($filename)->andReturn(false);
        $status = $manager->copy("fs1://{$filename}", "fs2://{$filename}");
        $this->assertFalse($status);

        $buffer = tmpfile();
        $fs1->shouldReceive('readStream')->once()->with($filename)->andReturn($buffer);
        $fs2->shouldReceive('writeStream')->once()->with($filename, $buffer)->andReturn(false);
        $status = $manager->copy("fs1://{$filename}", "fs2://{$filename}");
        $this->assertFalse($status);

        $buffer = tmpfile();
        $fs1->shouldReceive('readStream')->once()->with($filename)->andReturn($buffer);
        $fs2->shouldReceive('writeStream')->once()->with($filename, $buffer)->andReturn(true);
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
        $buffer = tmpfile();
        $fs1->shouldReceive('readStream')->with($filename)->andReturn($buffer);
        $fs2->shouldReceive('writeStream')->with($filename, $buffer)->andReturn(false);
        $code = $manager->move("fs1://{$filename}", "fs2://{$filename}");
        $this->assertFalse($code);

        $manager->shouldReceive('copy')->with("fs1://{$filename}", "fs2://{$filename}")->andReturn(true);
        $manager->shouldReceive('delete')->with("fs1://{$filename}")->andReturn(true);
        $code = $manager->move("fs1://{$filename}", "fs2://{$filename}");

        $this->assertTrue($code);
    }

    protected function mockFilesystem()
    {
        $mock = Mockery::mock('League\Flysystem\FilesystemInterface');
        $mock->shouldReceive('listContents')->andReturn([
           ['path' => 'path.txt', 'type' => 'file'],
           ['path' => 'dirname/path.txt', 'type' => 'file'],
        ]);

        return $mock;
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
        $mock = Mockery::mock('League\Flysystem\FilesystemInterface');
        $mock->shouldReceive('listWith')->with(['timestamp'], 'file.ext', false)->once()->andReturn($response);
        $manager->mountFilesystem('prot', $mock);
        $this->assertEquals($response, $manager->listWith(['timestamp'], 'prot://file.ext', false));
    }
}
