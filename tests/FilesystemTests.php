<?php

use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\Util;
use Prophecy\Argument;
use Prophecy\Argument\Token\TypeToken;
use Prophecy\Prophecy\ObjectProphecy;
use PHPUnit\Framework\TestCase;

class FilesystemTests extends TestCase
{
    use \PHPUnitHacks;

    /**
     * @var ObjectProphecy
     */
    protected $prophecy;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var TypeToken
     */
    protected $config;

    /**
     * @var Config
     */
    private $filesystemConfig;

    /**
     * @before
     */
    public function setupAdapter()
    {
        $this->prophecy = $this->prophesize('League\\Flysystem\\AdapterInterface');
        $this->adapter = $this->prophecy->reveal();
        $this->filesystemConfig = new Config();
        $this->filesystem = new Filesystem($this->adapter, $this->filesystemConfig);
        $this->config = Argument::type('League\\Flysystem\\Config');
    }

    public function testGetAdapter()
    {
        $this->assertEquals($this->adapter, $this->filesystem->getAdapter());
    }

    public function testGetConfig()
    {
        $this->assertInstanceOf('League\\Flysystem\\Config', $this->filesystem->getConfig());
    }

    public function testHas()
    {
        $this->prophecy->has('path.txt')->willReturn(true);
        $this->assertTrue($this->filesystem->has('path.txt'));
    }

    public function testWrite()
    {
        $path = 'path.txt';
        $contents = 'contents';
        $this->prophecy->has($path)->willReturn(false);
        $this->prophecy->write($path, $contents, $this->config)->willReturn(compact('path', 'contents'));
        $this->assertTrue($this->filesystem->write($path, $contents));
    }

    public function testWriteWithoutAsserts()
    {
        $this->filesystemConfig->set('disable_asserts', true);
        $path = 'path.txt';
        $contents = 'contents';
        $this->prophecy->write($path, $contents, $this->config)->willReturn(compact('path', 'contents'));
        $this->assertTrue($this->filesystem->write($path, $contents));
    }

    public function testWriteStream()
    {
        $path = 'path.txt';
        $stream = tmpfile();
        $this->prophecy->has($path)->willReturn(false);
        $this->prophecy->writeStream($path, $stream, $this->config)->willReturn(compact('path'));
        $this->assertTrue($this->filesystem->writeStream($path, $stream));
        fclose($stream);
    }

    public function testUpdate()
    {
        $path = 'path.txt';
        $contents = 'contents';
        $this->prophecy->has($path)->willReturn(true);
        $this->prophecy->update($path, $contents, $this->config)->willReturn(compact('path', 'contents'));
        $this->assertTrue($this->filesystem->update($path, $contents));
    }

    public function testUpdateStream()
    {
        $path = 'path.txt';
        $stream = tmpfile();
        $this->prophecy->has($path)->willReturn(true);
        $this->prophecy->updateStream($path, $stream, $this->config)->willReturn(compact('path'));
        $this->assertTrue($this->filesystem->updateStream($path, $stream));
        fclose($stream);
    }

    public function testPutNew()
    {
        $path = 'path.txt';
        $contents = 'contents';
        $this->prophecy->has($path)->willReturn(false);
        $this->prophecy->write($path, $contents, $this->config)->willReturn(compact('path', 'contents'));
        $this->assertTrue($this->filesystem->put($path, $contents));
    }

    public function testPutNewStream()
    {
        $path = 'path.txt';
        $stream = tmpfile();
        $this->prophecy->has($path)->willReturn(false);
        $this->prophecy->writeStream($path, $stream, $this->config)->willReturn(compact('path'));
        $this->assertTrue($this->filesystem->putStream($path, $stream));
        fclose($stream);
    }

    public function testPutUpdate()
    {
        $path = 'path.txt';
        $contents = 'contents';
        $this->prophecy->has($path)->willReturn(true);
        $this->prophecy->update($path, $contents, $this->config)->willReturn(compact('path', 'contents'));
        $this->assertTrue($this->filesystem->put($path, $contents));
    }

    public function testPutUpdateStream()
    {
        $path = 'path.txt';
        $stream = tmpfile();
        $this->prophecy->has($path)->willReturn(true);
        $this->prophecy->updateStream($path, $stream, $this->config)->willReturn(compact('path'));
        $this->assertTrue($this->filesystem->putStream($path, $stream));
        fclose($stream);
    }

    public function testPutStreamInvalid()
    {
        $this->expectException('InvalidArgumentException');
        $this->filesystem->putStream('path.txt', '__INVALID__');
    }

    public function testWriteStreamInvalid()
    {
        $this->expectException('InvalidArgumentException');
        $this->filesystem->writeStream('path.txt', '__INVALID__');
    }

    public function testUpdateStreamInvalid()
    {
        $this->expectException('InvalidArgumentException');
        $this->filesystem->updateStream('path.txt', '__INVALID__');
    }

    public function testReadAndDelete()
    {
        $path = 'path.txt';
        $output = '__CONTENTS__';
        $this->prophecy->has($path)->willReturn(true);
        $this->prophecy->read($path)->willReturn(['contents' => $output]);
        $this->prophecy->delete($path)->willReturn(true);
        $response = $this->filesystem->readAndDelete($path);
        $this->assertEquals($output, $response);
    }

    public function testReadAndDeleteFailedRead()
    {
        $path = 'path.txt';
        $this->prophecy->has($path)->willReturn(true);
        $this->prophecy->read($path)->willReturn(false);
        $response = $this->filesystem->readAndDelete($path);
        $this->assertFalse($response);
    }

    public function testRead()
    {
        $path = 'path.txt';
        $output = '__CONTENTS__';
        $this->prophecy->has($path)->willReturn(true);
        $this->prophecy->read($path)->willReturn(['contents' => $output]);
        $response = $this->filesystem->read($path);
        $this->assertEquals($response, $output);
    }

    public function testReadStream()
    {
        $path = 'path.txt';
        $output = '__CONTENTS__';
        $this->prophecy->has($path)->willReturn(true);
        $this->prophecy->readStream($path)->willReturn(['stream' => $output]);
        $response = $this->filesystem->readStream($path);
        $this->assertEquals($response, $output);
    }

    public function testReadStreamFail()
    {
        $path = 'path.txt';
        $this->prophecy->has($path)->willReturn(true);
        $this->prophecy->readStream($path)->willReturn(false);
        $response = $this->filesystem->readStream($path);
        $this->assertFalse($response);
    }

    public function testRename()
    {
        $old = 'old.txt';
        $new = 'new.txt';
        $this->prophecy->has(Argument::any())->willReturn(true, false);
        $this->prophecy->rename($old, $new)->willReturn(true);
        $response = $this->filesystem->rename($old, $new);
        $this->assertTrue($response);
    }

    public function testCopy()
    {
        $old = 'old.txt';
        $new = 'new.txt';
        $this->prophecy->has(Argument::any())->willReturn(true, false);
        $this->prophecy->copy($old, $new)->willReturn(true);
        $response = $this->filesystem->copy($old, $new);
        $this->assertTrue($response);
    }

    public function testCopyWithoutAsserts()
    {
        $old = 'old.txt';
        $new = 'new.txt';
        $this->filesystemConfig->set('disable_asserts', true);
        $this->prophecy->copy($old, $new)->willReturn(true);
        $response = $this->filesystem->copy($old, $new);
        $this->assertTrue($response);
    }

    public function testDeleteDirRootViolation()
    {
        $this->expectException('League\Flysystem\RootViolationException');
        $this->filesystem->deleteDir('');
    }

    public function testDeleteDir()
    {
        $this->prophecy->deleteDir('dirname')->willReturn(true);
        $response = $this->filesystem->deleteDir('dirname');
        $this->assertTrue($response);
    }

    public function testCreateDir()
    {
        $this->prophecy->createDir('dirname', $this->config)->willReturn(['path' => 'dirname', 'type' => 'dir']);
        $output = $this->filesystem->createDir('dirname');
        $this->assertTrue($output);
    }

    public function metaGetterProvider()
    {
        return [
            ['getSize', 1234],
            ['getVisibility', 'public'],
            ['getMimetype', 'text/plain'],
            ['getTimestamp', 2345],
            ['getMetadata', [
                'path' => 'success.txt',
                'size' => 1234,
                'visibility' => 'public',
                'mimetype' => 'text/plain',
                'timestamp' => 2345,
            ]],
        ];
    }

    /**
     * @dataProvider metaGetterProvider
     */
    public function testMetaGetterSuccess($method, $value)
    {
        $path = 'success.txt';
        $this->prophecy->has($path)->willReturn(true);
        $this->prophecy->{$method}($path)->willReturn([
            'path' => $path,
            'size' => 1234,
            'visibility' => 'public',
            'mimetype' => 'text/plain',
            'timestamp' => 2345,
        ]);
        $output = $this->filesystem->{$method}($path);
        $this->assertEquals($value, $output);
    }

    /**
     * @dataProvider metaGetterProvider
     */
    public function testMetaGetterFails($method)
    {
        $path = 'success.txt';
        $this->prophecy->has($path)->willReturn(true);
        $this->prophecy->{$method}($path)->willReturn(false);
        $output = $this->filesystem->{$method}($path);
        $this->assertFalse($output);
    }

    public function testAssertPresentThrowsException()
    {
        $this->expectException('League\Flysystem\FileExistsException');
        $this->prophecy->has('path.txt')->willReturn(true);
        $this->filesystem->write('path.txt', 'contents');
    }

    public function testAssertAbsentThrowsException()
    {
        $this->expectException('League\Flysystem\FileNotFoundException');
        $this->prophecy->has('path.txt')->willReturn(false);
        $this->filesystem->read('path.txt');
    }

    public function testSetVisibility()
    {
        $path = 'path.txt';
        $this->prophecy->has($path)->willReturn(true);
        $this->prophecy->setVisibility($path, 'public')->willReturn(['path' => $path, 'visibility' => 'public']);
        $output = $this->filesystem->setVisibility($path, 'public');
        $this->assertTrue($output);
    }

    public function testSetVisibilityFail()
    {
        $path = 'path.txt';
        $this->prophecy->has($path)->willReturn(true);
        $this->prophecy->setVisibility($path, 'public')->willReturn(false);
        $output = $this->filesystem->setVisibility($path, 'public');
        $this->assertFalse($output);
    }

    public function testGetFile()
    {
        $path = 'path.txt';
        $this->prophecy->has($path)->willReturn(true);
        $this->prophecy->getMetadata($path)->willReturn([
            'path' => $path,
            'type' => 'file',
        ]);

        $output = $this->filesystem->get($path);
        $this->assertInstanceOf('League\Flysystem\File', $output);
    }

    public function testGetDirectory()
    {
        $path = 'path';
        $this->prophecy->has($path)->willReturn(true);
        $this->prophecy->getMetadata($path)->willReturn([
            'path' => $path,
            'type' => 'dir',
        ]);

        $output = $this->filesystem->get($path);
        $this->assertInstanceOf('League\Flysystem\Directory', $output);
    }

    public function testListContents()
    {
        $rawListing = [
           ['path' => 'other_root/file.txt'],
           ['path' => 'valid/to_deep/file.txt'],
           ['path' => 'valid/file.txt'],
           ['path' => 'valid/a-valid-file.txt'],
        ];

        $expected = [
            Util::pathinfo('valid/a-valid-file.txt'),
            Util::pathinfo('valid/file.txt'),
        ];

        $this->prophecy->listContents('valid', false)->willReturn($rawListing);
        $output = $this->filesystem->listContents('valid', false);
        $this->assertEquals($expected, $output);
    }

    public function testListContentZeroName()
    {
        $rawListing = [
            // files
            ['path' => 0],
            ['path' => '0'],
            ['path' => ''],
            // directories
            ['path' => 0, 'type' => 'dir'],
            ['path' => '0', 'type' => 'dir'],
            ['path' => '', 'type' => 'dir']
        ];
        $this->prophecy->listContents('', false)->willReturn($rawListing);
        $output = $this->filesystem->listContents('', false);
        $this->assertCount(2, $output);
    }

    public function testListContentsRecursize()
    {
        $rawListing = [
           ['path' => 'other_root/file.txt'],
           ['path' => 'valid/to_deep/file.txt'],
           ['path' => 'valid/file.txt'],
           ['path' => 'valid/a-valid-file.txt'],
        ];
        $expected = [
            Util::pathinfo('valid/a-valid-file.txt'),
            Util::pathinfo('valid/file.txt'),
            Util::pathinfo('valid/to_deep/file.txt'),
        ];
        $this->prophecy->listContents('valid', true)->willReturn($rawListing);
        $output = $this->filesystem->listContents('valid', true);
        $this->assertEquals($expected, $output);

        $expected = [
            Util::pathinfo('other_root/file.txt'),
            Util::pathinfo('valid/a-valid-file.txt'),
            Util::pathinfo('valid/file.txt'),
            Util::pathinfo('valid/to_deep/file.txt'),
        ];
        $this->prophecy->listContents('', true)->willReturn($rawListing);
        $output = $this->filesystem->listContents('', true);
        $this->assertEquals($expected, $output);
    }
    public function testListContentsSubDirectoryMatches()
    {
        $rawListing = [['path' => 'a/dir/file.txt']];
        $this->prophecy->listContents('dir', true)->willReturn($rawListing);
        $output = $this->filesystem->listContents('dir', true);
        $this->assertEquals([], $output);
    }

    public function testInvalidPluginCall()
    {
        $this->expectException('BadMethodCallException');
        $this->filesystem->invalidCall();
    }
}
