<?php

namespace League\Flysystem\Util;

use PHPUnit\Framework\TestCase;

class UtilModeTests extends TestCase
{
    public function testNumbersPassThrough()
    {
        $this->assertEquals(0755, Mode::mode(0755));
        $this->assertEquals(493, Mode::mode(493));
    }
    
    public function testOctetStringsAreConverted()
    {
        $this->assertEquals(0755, Mode::mode('755'));
        $this->assertEquals(0755, Mode::mode('0755'));
        $this->assertEquals(04755, Mode::mode('4755'));
        $this->assertEquals(04755, Mode::mode('04755'));
    }
    
    public function testFlagStringsAreConverted()
    {
        $this->assertEquals(0755, Mode::mode('rwxr-xr-x'));
        $this->assertEquals(0264, Mode::mode('-w-rw-r--'));
    }
    
    public function testSpecialOctetIsSet()
    {
        $this->assertEquals(04755, Mode::mode('rwsr-xr-x'));
        $this->assertEquals(02755, Mode::mode('rwxr-sr-x'));
        $this->assertEquals(01755, Mode::mode('rwxr-xr-t'));
        $this->assertEquals(07644, Mode::mode('rwSr-Sr-T'));
    }
    
    public function testCharDDoesntMatter()
    {
        $this->assertEquals(0755, Mode::mode('drwxr-xr-x'));
        $this->assertEquals(0264, Mode::mode('-w-rw-r--'));
        
        $this->assertEquals(04755, Mode::mode('drwsr-xr-x'));
        $this->assertEquals(02755, Mode::mode('drwxr-sr-x'));
        $this->assertEquals(01755, Mode::mode('drwxr-xr-t'));
        $this->assertEquals(07644, Mode::mode('drwSr-Sr-T'));
    }
}
