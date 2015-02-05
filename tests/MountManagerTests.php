<?php

use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use Phly\Http\Uri;

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

    public function dataProviderTestGetFilesystemRoot()
    {
        return [
            [__DIR__ . '/foo.bar', '/'],
            ['file://' . __DIR__ . '/foo.bar', '/'],
            ['ftp://usr@ftphost.com/folder1/foo.bar', '/'],
            ['ftp://usr:pass@ftphost.com/folder1/foo.bar', '/'],
            ['ftp://usr:pass@ftphost.com/foo.bar', '/'],
            ['ftp://usr:pass@ftphost.com', '/'],
        ];
    }

    /**
     * @param string $path
     * @param string $root
     * @dataProvider dataProviderTestGetFilesystemRoot
     */
    public function testGetFilesystemRoot($path, $root)
    {
        $mountManager = new MountManager();
        $this->assertEquals($root, $mountManager->getFilesystemRoot(new Uri($path)));
    }

    public function dataProviderGetFilesystemPrefix()
    {
        return [
            [__DIR__ . '/foo.bar', 'file'],
            ['file://' . __DIR__ . '/foo.bar', 'file'],
            ['ftp://usr@ftphost.com/folder1/foo.bar', 'ftp://usr@ftphost.com/'],
            ['ftp://usr:pass@ftphost.com/folder1/foo.bar', 'ftp://usr:pass@ftphost.com/'],
            ['ftp://usr:pass@ftphost.com/foo.bar', 'ftp://usr:pass@ftphost.com/'],
            ['ftp://usr:pass@ftphost.com', 'ftp://usr:pass@ftphost.com/'],
        ];
    }

    /**
     * @param string $path
     * @param string $prefix
     * @dataProvider dataProviderGetFilesystemPrefix
     */
    public function testGetFilesystemPrefix($path, $prefix)
    {
        $mountManager = new MountManager();
        $this->assertEquals($prefix, $mountManager->getFilesystemPrefix(new Uri($path)));
    }

    public function dataProviderTestCopy()
    {
        $data = [
              ['file://' . __DIR__ . '/../changelog.md', 'file://' . __DIR__ . '/files/changelog.md'],
        ];

        if ($_ENV['ftp.enable_tests'] == 1) {
            $data[] = ['file://' . __DIR__ . '/../changelog.md', $_ENV['ftp.uri'] . '/changelog.md'];
        }

        return $data;
    }

    /**
     * @param string $sourcePath
     * @param string $targetPath
     * @dataProvider dataProviderTestCopy
     */
    public function testCopy($sourcePath, $targetPath)
    {
        $filesToCopy = 3;
        $mountManager = (new MountManager())->setAutomount(true);
        // cleanup
        for ($i = 1; $i <= $filesToCopy; ++$i) {
            $iTargetPath = $targetPath . $i;
            if ($mountManager->has($iTargetPath)) {
                $mountManager->delete($iTargetPath);
            }
        }

        for ($i = 0; $i < $filesToCopy; $i++) {
            $iTargetPath = $targetPath . $i;
            $mountManager->copy($sourcePath, $iTargetPath);
            $this->assertTrue($mountManager->has($iTargetPath));
        }

        // cleanup
        for ($i = 1; $i <= $filesToCopy; ++$i) {
            $iTargetPath = $targetPath . $i;
            if ($mountManager->has($iTargetPath)) {
                $mountManager->delete($iTargetPath);
            }
        }
    }
}
