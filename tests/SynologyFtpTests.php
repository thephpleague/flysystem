<?php

namespace League\Flysystem\Adapter;


class SynologyFtpTests extends \PHPUnit_Framework_TestCase
{
    protected $options = array(
        'host' => 'example.org',
        'port' => 40,
        'ssl' => true,
        'timeout' => 35,
        'root' => '/somewhere',
        'permPublic' => 0777,
        'permPrivate' => 0000,
        'passive' => false,
        'username' => 'user',
        'password' => 'password',
    );

    public function testInstantiable()
    {
        if (!defined('FTP_BINARY')) {
            $this->markTestSkipped('The FTP_BINARY constant is not defined');
            return;
        }

        $adapter = new SynologyFtp($this->options);
        $this->assertEquals('example.org', $adapter->getHost());
        $this->assertEquals(40, $adapter->getPort());
        $this->assertEquals(35, $adapter->getTimeout());
        $this->assertEquals('/somewhere/', $adapter->getRoot());
        $this->assertEquals(0777, $adapter->getPermPublic());
        $this->assertEquals(0000, $adapter->getPermPrivate());
        $this->assertEquals('user', $adapter->getUsername());
        $this->assertEquals('password', $adapter->getPassword());
        $listing = $adapter->listContents('', true);
        $this->assertInternalType('array', $listing);
        $this->assertFalse($adapter->has('syno.not.found'));
        $this->assertFalse($adapter->getVisibility('syno.not.found'));
        $this->assertFalse($adapter->getSize('syno.not.found'));
        $this->assertFalse($adapter->getMimetype('syno.not.found'));
        $this->assertFalse($adapter->getTimestamp('syno.not.found'));
        $this->assertFalse($adapter->write('write.fail', 'contents'));
        $this->assertFalse($adapter->writeStream('write.fail', tmpfile()));
        $this->assertFalse($adapter->update('write.fail', 'contents'));
        $this->assertFalse($adapter->setVisibility('chmod.fail', 'private'));
        $this->assertTrue($adapter->rename('a', 'b'));
        $this->assertTrue($adapter->delete('a'));
        $this->assertFalse($adapter->deleteDir('some.nested/rmdir.fail'));
        $this->assertFalse($adapter->deleteDir('rmdir.nested.fail'));
        $this->assertTrue($adapter->deleteDir('somewhere'));
        $result = $adapter->read('something.txt');
        $this->assertEquals('contents', $result['contents']);
        $result = $adapter->getMimetype('something.txt');
        $this->assertEquals('text/plain', $result['mimetype']);
        $this->assertFalse($adapter->createDir('some.nested/mkdir.fail'));
        $this->assertInternalType('array', $adapter->write('syno.unknowndir/file.txt', 'contents', 'public'));
        $this->assertInternalType('array', $adapter->writeStream('syno.unknowndir/file.txt', tmpfile(), 'public'));
        $this->assertInternalType('array', $adapter->updateStream('syno.unknowndir/file.txt', tmpfile(), 'public'));
        $adapter->deleteDir('');
        $this->assertInternalType('array', $adapter->getTimestamp('some/file.ext'));
    }

    /**
     * @depends testInstantiable
     * @expectedException RuntimeException
     */
    public function testConnectFail()
    {
        $adapter = new SynologyFtp(array('host' => 'fail.me', 'ssl' => false, 'transferMode' => FTP_BINARY));
        $adapter->connect();
    }

    /**
     * @depends testInstantiable
     */
    public function testRawlistFail()
    {
        $adapter = new SynologyFtp($this->options);
        $result = $adapter->listContents('fail.rawlist');
        $this->assertEquals(array(), $result);
    }

    /**
     * @depends testInstantiable
     * @expectedException RuntimeException
     */
    public function testConnectFailSsl()
    {
        $adapter = new SynologyFtp(array('host' => 'fail.me', 'ssl' => true));
        $adapter->connect();
    }

    /**
     * @depends testInstantiable
     * @expectedException RuntimeException
     */
    public function testLoginFailSsl()
    {
        $adapter = new SynologyFtp(array('host' => 'login.fail', 'ssl' => true));
        $adapter->connect();
    }

    /**
     * @depends testInstantiable
     * @expectedException RuntimeException
     */
    public function testRootFailSsl()
    {
        $adapter = new SynologyFtp(array('host' => 'chdir.fail', 'ssl' => true, 'root' => 'somewhere'));
        $adapter->connect();
    }

    /**
     * @depends testInstantiable
     * @expectedException RuntimeException
     */
    public function testPassiveFailSsl()
    {
        $adapter = new SynologyFtp(array('host' => 'pasv.fail', 'ssl' => true, 'root' => 'somewhere'));
        $adapter->connect();
    }
}
