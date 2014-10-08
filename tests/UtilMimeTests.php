<?php

namespace League\Flysystem\Util;

use League\Flysystem\Util;

$passthru = true;

function class_exists($class_name, $autoload = true) {
    global $passthru;

    if ($passthru) {
        return \class_exists($class_name, $autoload);
    }

    return false;
}

class MimeTests extends \PHPUnit_Framework_TestCase
{
    public function pathAndContentProvider()
    {
        return array(
            array('/some/file.css', 'body { background: #000; } ', 'text/css'),
            array('/some/file.txt', 'body { background: #000; } ', 'text/plain'),
            array('/1x1', base64_decode('R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs='), 'image/gif')
        );
    }

    /**
     * @dataProvider  pathAndContentProvider
     */
    public function testGuessMimeTypeFallback($path, $content, $expected)
    {
        global $passthru;
        $passthru = false;
        $mimeType = Util::guessMimeType($path, $content);
        $this->assertEquals($expected, $mimeType);
        $passthru = true;
    }

    /**
     * @dataProvider  pathAndContentProvider
     */
    public function testGuessMimeType($path, $content, $expected)
    {
        $mimeType = Util::guessMimeType($path, $content);
        $this->assertEquals($expected, $mimeType);
    }
}