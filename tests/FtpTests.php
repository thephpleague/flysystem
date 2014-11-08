<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Config;

function ftp_ssl_connect($host)
{
    if ($host === 'fail.me') {
        return false;
    }

    return $host;
}

function ftp_delete($conn, $path)
{
    if (strpos($path, 'rm.fail.txt')) {
        return false;
    }

    return true;
}

function ftp_rmdir($connection, $dirname)
{
    if (strpos($dirname, 'rmdir.fail') !== false) {
        return false;
    }

    return true;
}

function ftp_connect($host)
{
    return ftp_ssl_connect($host);
}

function ftp_pasv($connection)
{
    if ($connection === 'pasv.fail') {
        return false;
    }

    return true;
}

function ftp_rename()
{
    return true;
}

function ftp_close()
{
    return true;
}

function ftp_login($connection)
{
    if ($connection === 'login.fail') {
        trigger_error('FTP login failed!!', E_WARNING);
        return false;
    }

    return true;
}

function ftp_chdir($connection)
{
    if ($connection === 'chdir.fail') {
        return false;
    }

    return true;
}

function ftp_pwd($connection)
{
    return 'dirname';
}

function ftp_raw($connection, $command)
{
    if ($command === 'STAT not.found') {
        return array(0 => '213-Status follows:', 1 => '213 End of status');
    }

    if ($command === 'STAT syno.not.found') {
        return array(0 => '211- status of syno.not.found:', 1 => 'ftpd: assd: No such file or directory.' ,2 => '211 End of status');
    }

    if ($command === 'syno.unknowndir') {
        return array(0 => '211- status of syno.unknowndir:', 1 => 'ftpd: assd: No such file or directory.' ,2 => '211 End of status');
    }

    if (strpos($command, 'unknowndir') !== false) {
        return false;
    }

    return array( 0 => '211-Status of somewhere/folder/dummy.txt:', 1 => ' -rw-r--r-- 1 ftp ftp 0 Nov 24 13:59 somewhere/folder/dummy.txt', 2 => '211 End of status' );
}

function ftp_rawlist($connection, $directory)
{
    if (strpos($directory, 'fail.rawlist') !== false) {
        return false;
    }

    if (strpos($directory, 'rmdir.nested.fail') !== false) {
        return array(
            'drwxr-xr-x   2 ftp      ftp          4096 Oct 13  2012 .',
            'drwxr-xr-x   4 ftp      ftp          4096 Nov 24 13:58 ..',
            '-rw-r--r--   1 ftp      ftp           409 Oct 13  2012 rm.fail.txt',
        );
    }

    return array(
        'drwxr-xr-x   4 ftp      ftp          4096 Nov 24 13:58 .',
        'drwxr-xr-x  16 ftp      ftp          4096 Sep  2 13:01 ..',
        'drwxr-xr-x   2 ftp      ftp          4096 Oct 13  2012 cgi-bin',
        'drwxr-xr-x   2 ftp      ftp          4096 Nov 24 13:59 folder',
        '-rw-r--r--   1 ftp      ftp           409 Oct 13  2012 index.html',
        '',
        'somewhere/cgi-bin:',
        'drwxr-xr-x   2 ftp      ftp          4096 Oct 13  2012 .',
        'drwxr-xr-x   4 ftp      ftp          4096 Nov 24 13:58 ..',
        '',
         'somewhere/folder:',
         'drwxr-xr-x   2 ftp      ftp          4096 Nov 24 13:59 .',
         'drwxr-xr-x   4 ftp      ftp          4096 Nov 24 13:58 ..',
         '-rw-r--r--   1 ftp      ftp             0 Nov 24 13:59 dummy.txt',
    );
}

function ftp_mkdir($connection, $dirname)
{
    if (strpos($dirname, 'mkdir.fail') !== false) {
        return false;
    }

    return true;
}

function ftp_fput($connection, $path)
{
    if (strpos($path, 'write.fail') !== false) {
        return false;
    }

    return true;
}

function ftp_fget($connection, $resource, $path)
{
    if (strpos($path, 'not.found') !== false) {
        return false;
    }

    \fwrite($resource, 'contents');
    rewind($resource);

    return true;
}

function ftp_nlist($connection, $directory)
{
    return array('./some.nested');
}

function ftp_chmod($connection, $mode, $path)
{
    if (strpos($path, 'chmod.fail') !== false) {
        return false;
    }

    return true;
}

class FtpTests extends \PHPUnit_Framework_TestCase
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
        if (! defined('FTP_BINARY')) {
            $this->markTestSkipped('The FTP_BINARY constant is not defined');
            return;
        }

        $adapter = new Ftp($this->options);
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
        $this->assertFalse($adapter->has('not.found'));
        $this->assertFalse($adapter->getVisibility('not.found'));
        $this->assertFalse($adapter->getSize('not.found'));
        $this->assertFalse($adapter->getMimetype('not.found'));
        $this->assertFalse($adapter->getTimestamp('not.found'));
        $this->assertFalse($adapter->write('write.fail', 'contents', new Config));
        $this->assertFalse($adapter->writeStream('write.fail', tmpfile(), new Config));
        $this->assertFalse($adapter->update('write.fail', 'contents', new Config));
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
        $this->assertFalse($adapter->createDir('some.nested/mkdir.fail', new Config));
        $this->assertInternalType('array', $adapter->write('unknowndir/file.txt', 'contents', new Config(['visibility' => 'public'])));
        $this->assertInternalType('array', $adapter->writeStream('unknowndir/file.txt', tmpfile(), new Config(['visibility' => 'public'])));
        $this->assertInternalType('array', $adapter->updateStream('unknowndir/file.txt', tmpfile(), new Config));
        $adapter->deleteDir('');
        $this->assertInternalType('array', $adapter->getTimestamp('some/file.ext'));
    }

    /**
     * @depends testInstantiable
     * @expectedException RuntimeException
     */
    public function testConnectFail()
    {
        $adapter = new Ftp(array('host' => 'fail.me', 'ssl' => false, 'transferMode' => FTP_BINARY));
        $adapter->connect();
    }

    /**
     * @depends testInstantiable
     */
    public function testRawlistFail()
    {
        $adapter = new Ftp($this->options);
        $result = $adapter->listContents('fail.rawlist');
        $this->assertEquals(array(), $result);
    }

    /**
     * @depends testInstantiable
     * @expectedException RuntimeException
     */
    public function testConnectFailSsl()
    {
        $adapter = new Ftp(array('host' => 'fail.me', 'ssl' => true));
        $adapter->connect();
    }

    /**
     * @depends testInstantiable
     * @expectedException RuntimeException
     */
    public function testLoginFailSsl()
    {
        $adapter = new Ftp(array('host' => 'login.fail', 'ssl' => true));
        $adapter->connect();
    }

    /**
     * @depends testInstantiable
     * @expectedException RuntimeException
     */
    public function testRootFailSsl()
    {
        $adapter = new Ftp(array('host' => 'chdir.fail', 'ssl' => true, 'root' => 'somewhere'));
        $adapter->connect();
    }

    /**
     * @depends testInstantiable
     * @expectedException RuntimeException
     */
    public function testPassiveFailSsl()
    {
        $adapter = new Ftp(array('host' => 'pasv.fail', 'ssl' => true, 'root' => 'somewhere'));
        $adapter->connect();
    }
}
