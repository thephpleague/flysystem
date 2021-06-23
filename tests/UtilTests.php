<?php

namespace League\Flysystem;

use LogicException;
use PHPUnit\Framework\TestCase;

class UtilTests extends TestCase
{

    public function testEmulateDirectories()
    {
        $input = [
            ['dirname' => '', 'filename' => 'dummy', 'path' => 'dummy', 'type' => 'file'],
            ['dirname' => 'something', 'filename' => 'dummy', 'path' => 'something/dummy', 'type' => 'file'],
            ['dirname' => 'something', 'path' => 'something/dirname', 'type' => 'dir'],
        ];
        $output = Util::emulateDirectories($input);
        $this->assertCount(4, $output);
    }
    public function testEmulateDirectoriesWithNumberZeroDirectoryName()
    {
        $input = [
            ['dirname' => 'something/0', 'path' => 'something/0/dirname', 'type' => 'dir'],
            ['dirname' => '0/other', 'path' => '0/other/dir', 'type' => 'dir'],
        ];
        $output = Util::emulateDirectories($input);

        $this->assertCount(6, $output);
    }

    public function testContentSize()
    {
        $this->assertEquals(5, Util::contentSize('12345'));
        $this->assertEquals(3, Util::contentSize('135'));
    }

    /**
     * @dataProvider dbCorruptedPath
     */
    public function testRejectingPathWithFunkyWhitespace($path)
    {
        $this->expectException(CorruptedPathDetected::class);
        Util::normalizePath($path);
    }

    /**
     * @return array
     */
    public function dbCorruptedPath()
    {
        return [["some\0/path.txt"], ["s\x09i.php"]];
    }

    public function mapProvider()
    {
        return [
            [['from.this' => 'value'], ['from.this' => 'to.this', 'other' => 'other'], ['to.this' => 'value']],
            [['from.this' => 'value', 'no.mapping' => 'lost'], ['from.this' => 'to.this'], ['to.this' => 'value']],
        ];
    }

    /**
     * @dataProvider  mapProvider
     */
    public function testMap($from, $map, $expected)
    {
        $result = Util::map($from, $map);
        $this->assertEquals($expected, $result);
    }

    public function dirnameProvider()
    {
        return [
            ['filename.txt', ''],
            ['dirname/filename.txt', 'dirname'],
            ['dirname/subdir', 'dirname'],
        ];
    }

    /**
     * @dataProvider  dirnameProvider
     */
    public function testDirname($input, $expected)
    {
        $result = Util::dirname($input);
        $this->assertEquals($expected, $result);
    }

    public function testEnsureConfig()
    {
        $this->assertInstanceOf('League\Flysystem\Config', Util::ensureConfig([]));
        $this->assertInstanceOf('League\Flysystem\Config', Util::ensureConfig(null));
        $this->assertInstanceOf('League\Flysystem\Config', Util::ensureConfig(new Config()));
    }

    public function testInvalidValueEnsureConfig()
    {
        $this->expectException(LogicException::class);
        Util::ensureConfig(false);
    }

    public function invalidPathProvider()
    {
        return [
            ['something/../../../hehe'],
            ['/something/../../..'],
            ['..'],
            ['something\\..\\..'],
            ['\\something\\..\\..\\dirname'],
        ];
    }

    /**
     * @dataProvider invalidPathProvider
     */
    public function testOutsideRootPath($path)
    {
        $this->expectException(LogicException::class);
        Util::normalizePath($path);
    }

    public function pathProvider()
    {
        return [
            ['.', ''],
            ['/path/to/dir/.', 'path/to/dir'],
            ['/dirname/', 'dirname'],
            ['dirname/..', ''],
            ['dirname/../', ''],
            ['dirname./', 'dirname.'],
            ['dirname/./', 'dirname'],
            ['dirname/.', 'dirname'],
            ['./dir/../././', ''],
            ['/something/deep/../../dirname', 'dirname'],
            ['00004869/files/other/10-75..stl', '00004869/files/other/10-75..stl'],
            ['/dirname//subdir///subsubdir', 'dirname/subdir/subsubdir'],
            ['\dirname\\\\subdir\\\\\\subsubdir', 'dirname/subdir/subsubdir'],
            ['\\\\some\shared\\\\drive', 'some/shared/drive'],
            ['C:\dirname\\\\subdir\\\\\\subsubdir', 'C:/dirname/subdir/subsubdir'],
            ['C:\\\\dirname\subdir\\\\subsubdir', 'C:/dirname/subdir/subsubdir'],
            ['example/path/..txt', 'example/path/..txt'],
            ['\\example\\path.txt', 'example/path.txt'],
            ['\\example\\..\\path.txt', 'path.txt'],
        ];
    }

    /**
     * @dataProvider  pathProvider
     */
    public function testNormalizePath($input, $expected)
    {
        $result = Util::normalizePath($input);
        $double = Util::normalizePath(Util::normalizePath($input));
        $this->assertEquals($expected, $result);
        $this->assertEquals($expected, $double);
    }

    public function pathAndContentProvider()
    {
        return [
            ['/some/file.css', '.event { background: #000; } ', 'text/css'],
            ['/some/file.css', 'body { background: #000; } ', 'text/css'],
            ['/some/file.txt', 'body { background: #000; } ', 'text/plain'],
            ['/1x1', base64_decode('R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs='), 'image/gif'],
        ];
    }

    /**
     * @dataProvider  pathAndContentProvider
     */
    public function testGuessMimeType($path, $content, $expected)
    {
        $mimeType = Util::guessMimeType($path, $content);
        $this->assertEquals($expected, $mimeType);
    }

    public function testStreamSize()
    {
        $stream = tmpfile();
        fwrite($stream, 'aaa');
        $size = Util::getStreamSize($stream);
        $this->assertEquals(3, $size);
        fclose($stream);
    }

    public function testStreamSizeForUrl()
    {
        $stream = \fopen('https://flysystem.thephpleague.com/img/flysystem.svg', 'r');
        $this->assertNull(Util::getStreamSize($stream));
        fclose($stream);
    }

    public function testRewindStream()
    {
        $stream = tmpfile();
        fwrite($stream, 'something');
        $this->assertNotEquals(0, ftell($stream));
        Util::rewindStream($stream);
        $this->assertEquals(0, ftell($stream));
        fclose($stream);
    }

    public function testNormalizePrefix()
    {
        $this->assertEquals('test/', Util::normalizePrefix('test', '/'));
        $this->assertEquals('test/', Util::normalizePrefix('test/', '/'));
    }

    public function pathinfoPathProvider()
    {
        return [
            [''],
            ['.'],
            ['..'],
            ['...'],
            ['/.'],
            ['//.'],
            ['///.'],

            ['foo'],
            ['/foo'],
            ['/foo/bar'],
            ['/foo/bar/'],

            ['file.txt'],
            ['foo/file.txt'],
            ['/foo/file.jpeg'],

            ['.txt'],
            ['dir/.txt'],
            ['/dir/.txt'],

            ['foo/bar.'],
            ['foo/bar..'],
            ['foo/bar/.'],

            ['c:'],
            ['c:\\'],
            ['c:/'],
            ['c:file'],
            ['c:f:ile'],
            ['c:f:'],
            ['c:d:e:'],
            ['AB:file'],
            ['AB:'],
            ['d:\foo\bar'],
            ['E:\foo\bar\\'],
            ['f:\foo\bar:baz'],
            ['G:\foo\bar:'],
            ['c:/foo/bar'],
            ['c:/foo/bar/'],
            ['Y:\foo\bar.txt'],
            ['z:\foo\bar.'],
            ['foo\bar'],
        ];
    }

    /**
     * @dataProvider  pathinfoPathProvider
     */
    public function testPathinfo($path)
    {
        $expected = compact('path') + pathinfo($path) + ['dirname' => ''];

        if (isset($expected['dirname'])) {
            $expected['dirname'] = Util::normalizeDirname($expected['dirname']);
        }

        $this->assertSame($expected, Util::pathinfo($path));
    }

    public function testPathinfoHandlesUtf8()
    {
        if (setlocale(LC_ALL, 'C.UTF-8', 'C.utf8') === false) {
            $this->markTestSkipped('testPathinfoHandlesUtf8 requires UTF-8.');
        }

        $path = 'files/繁體中文字/test.txt';
        $expected = [
            'path' => 'files/繁體中文字/test.txt',
            'dirname' => 'files/繁體中文字',
            'basename' => 'test.txt',
            'extension' => 'txt',
            'filename' => 'test',
        ];
        $this->assertSame($expected, Util::pathinfo($path));

        $path = 'files/繁體中文字.txt';
        $expected = [
            'path' => 'files/繁體中文字.txt',
            'dirname' => 'files',
            'basename' => '繁體中文字.txt',
            'extension' => 'txt',
            'filename' => '繁體中文字',
        ];
        $this->assertSame($expected, Util::pathinfo($path));

        $path = '👨‍👩‍👧‍👦👨‍👩‍👦‍👦👨‍👩‍👧‍👧/繁體中文字.txt';
        $expected = [
            'path' => '👨‍👩‍👧‍👦👨‍👩‍👦‍👦👨‍👩‍👧‍👧/繁體中文字.txt',
            'dirname' => '👨‍👩‍👧‍👦👨‍👩‍👦‍👦👨‍👩‍👧‍👧',
            'basename' => '繁體中文字.txt',
            'extension' => 'txt',
            'filename' => '繁體中文字',
        ];
        $this->assertSame($expected, Util::pathinfo($path));

        $path = 'foo/bar.baz.😀😬😁';
        $expected = [
            'path' => 'foo/bar.baz.😀😬😁',
            'dirname' => 'foo',
            'basename' => 'bar.baz.😀😬😁',
            'extension' => '😀😬😁',
            'filename' => 'bar.baz',
        ];
        $this->assertSame($expected, Util::pathinfo($path));

        $path = '繁體中文字/👨‍👩‍👧‍👦.😺😸😹😻.😀😬😁';
        $expected = [
            'path' => '繁體中文字/👨‍👩‍👧‍👦.😺😸😹😻.😀😬😁',
            'dirname' => '繁體中文字',
            'basename' => '👨‍👩‍👧‍👦.😺😸😹😻.😀😬😁',
            'extension' => '😀😬😁',
            'filename' => '👨‍👩‍👧‍👦.😺😸😹😻',
        ];
        $this->assertSame($expected, Util::pathinfo($path));
    }
}
