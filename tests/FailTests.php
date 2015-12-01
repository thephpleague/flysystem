<?php

namespace League\Flysystem\Adapter
{
    function file_put_contents($name)
    {
        if (strpos($name, 'pleasefail') !== false) {
            return false;
        }

        return call_user_func_array('file_put_contents', func_get_args());
    }

    function file_get_contents($name)
    {
        if (strpos($name, 'pleasefail') !== false) {
            return false;
        }

        return call_user_func_array('file_get_contents', func_get_args());
    }
}

namespace League\Flysystem
{
    class FailTests extends \PHPUnit_Framework_TestCase
    {

        public function provideAdapters()
        {
            return [
                'Local adapter' => [new Adapter\Local(__DIR__.'/files')],
                'Atomic local adapter' => [new Adapter\AtomicLocal(__DIR__.'/files')],
            ];
        }

        /**
         * @dataProvider provideAdapters
         */
        public function testFails($adapter)
        {
            $this->assertFalse($adapter->write('pleasefail.txt', 'content', new Config()));
            $this->assertFalse($adapter->update('pleasefail.txt', 'content', new Config()));
            $this->assertFalse($adapter->read('pleasefail.txt'));
            $this->assertFalse($adapter->deleteDir('non-existing'));
        }

        public function testAtomicFails()
        {
            $adapter = new Adapter\AtomicLocal(__DIR__.'/files');
            $this->assertFalse($adapter->write('rename.fail', 'content', new Config()));
            $this->assertFalse($adapter->writeStream('false', tmpfile(), new Config()));
            $this->assertFalse($adapter->writeStream('rename.fail', tmpfile(), new Config()));
            $this->assertFalse($adapter->update('rename.fail', 'content', new Config()));
        }
    }

}
