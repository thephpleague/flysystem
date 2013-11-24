<?php

namespace Flysystem\Adapter;

function fopen($result)
{
    if (substr($result, -5) === 'false') {
        return false;
    }

    if (substr($result, -5) === 'dummy') {
        return 'dummy';
    }

    return call_user_func_array('fopen', func_get_args());
}

function fwrite($result)
{
    if (is_string($result)) {
        return 'dummy';
    }

    return call_user_func_array('fwrite', func_get_args());
}

function fclose($result)
{
    if (is_string($result) and substr($result, -5) === 'dummy') {
        return false;
    }

    return call_user_func_array('fclose', func_get_args());
}

class LocalAdapterTests extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->adapter = new Local(__DIR__.'/files');
    }

    public function testReadStream()
    {
        $adapter = $this->adapter;
        $adapter->write('file.txt', 'contents');
        $result = $adapter->readStream('file.txt');
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('stream', $result);
        $this->assertInternalType('resource', $result['stream']);
        $adapter->delete('file.txt');
    }

    public function testWriteStream()
    {
        $adapter = $this->adapter;
        $temp = tmpfile();
        fwrite($temp, 'dummy');
        rewind($temp);
        $adapter->writeStream('file.txt', $temp);
        fclose($temp);
        $this->assertTrue($adapter->has('file.txt'));
        $result = $adapter->read('file.txt');
        $this->assertEquals('dummy', $result['contents']);
        $adapter->delete('file.txt');
    }

    public function testUpdateStream()
    {
        $adapter = $this->adapter;
        $adapter->write('file.txt', 'initial');
        $temp = tmpfile();
        fwrite($temp, 'dummy');
        $adapter->updateStream('file.txt', $temp);
        fclose($temp);
        $this->assertTrue($adapter->has('file.txt'));
        $adapter->delete('file.txt');
    }

    public function testFailingStreamCalls()
    {
        $this->assertFalse($this->adapter->writeStream('false', tmpfile()));
        $this->assertFalse($this->adapter->writeStream('dummy', tmpfile()));
    }
}