<?php

namespace League\Flysystem\Util;

use PHPUnit\Framework\TestCase;

class UtilMimeTests extends TestCase
{
    public function testRetrievingAllMimetypes()
    {
        $map = MimeType::getExtensionToMimeTypeMap();
        $this->assertInternalType('array', $map);
        $this->assertEquals('application/epub+zip', $map['epub']);
    }

    public function testNoExtension()
    {
        $this->assertEquals('text/plain', MimeType::detectByFileExtension('dir/file'));
    }
}
