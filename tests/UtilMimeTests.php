<?php

namespace League\Flysystem\Util;

$passthru = true;

function class_exists($class_name, $autoload = true)
{
    global $passthru;

    if ($passthru) {
        return \class_exists($class_name, $autoload);
    }

    return false;
}

class UtilMimeTests extends \PHPUnit_Framework_TestCase
{
    public function testNoFinfoFallback()
    {
        global $passthru;
        $passthru = false;
        $this->assertNull(MimeType::detectByContent('string'));
        $passthru = true;
    }
}
