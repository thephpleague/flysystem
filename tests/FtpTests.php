<?php

namespace League\Flysystem\Adapter;

use DateTime;
use ErrorException;
use League\Flysystem\Config;
use League\Flysystem\NotSupportedException;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use const PHP_VERSION;

function ftp_systype($connection)
{
    static $connections = [
        'reconnect.me',
        'dont.reconnect.me',
    ];

    if (is_string($connection) && array_key_exists($connection, $connections)) {
        $connections[$connection]++;

        if (strpos($connection, 'dont') !== false || $connections[$connection] < 2) {
            return false;
        }
    }

    return 'LINUX';
}

function ftp_ssl_connect($host)
{
    if ($host === 'fail.me') {
        return false;
    }

    if ($host === 'disconnect.check') {
        return tmpfile();
    }

    return $host;
}

function ftp_delete($conn, $path)
{
    return ! strpos($path, 'rm.fail.txt');
}

function ftp_rmdir($connection, $dirname)
{
    return strpos($dirname, 'rmdir.fail') === false;
}

function ftp_connect($host)
{
    return ftp_ssl_connect($host);
}

function ftp_pasv($connection)
{
    return $connection !== 'pasv.fail';
}

function ftp_rename()
{
    return true;
}

function ftp_close($connection)
{
    if (is_resource($connection)) {
        return fclose($connection);
    }

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

function ftp_chdir($connection, $directory)
{
    if ($connection === 'chdir.fail') {
        return false;
    }

    if (in_array($directory, [
        'not.found',
        'windows.not.found',
        'syno.not.found',
        'rawlist-total-0.txt',
        'file1.txt',
        'file2.txt',
        'file3.txt',
        'file4.txt',
        'file5WithPadding.txt',
        'dir1',
        'file1.with-total-line.txt',
        'file1.with-invalid-line.txt',
    ], true)) {
        return false;
    }

    return $directory !== '0';
}

function ftp_pwd($connection)
{
    return 'dirname';
}

function ftp_raw($connection, $command)
{
    if ($connection === 'utf8.fail') {
        return [0 => '421 Service not available, closing control connection'];
    }

    if ($command === 'OPTS UTF8 ON') {
        if ($connection === 'utf8.alreadyActive') {
            return [0 => '202 UTF8 mode is always enabled. No need to send this command.'];
        }

        return [0 => '200 UTF8 set to on'];
    }

    if ($command === 'NOOP') {
        if (getenv('FTP_CLOSE_THROW') === 'DISCONNECT_CATCH') {
            return [0 => '500 Internal error'];
        }

        return [0 => '200 Zzz...'];
    }

    if ($command === 'STAT syno.not.found') {
        return [0 => '211- status of syno.not.found:', 1 => 'ftpd: assd: No such file or directory.' ,2 => '211 End of status'];
    }

    if ($command === 'syno.unknowndir') {
        return [0 => '211- status of syno.unknowndir:', 1 => 'ftpd: assd: No such file or directory.' ,2 => '211 End of status'];
    }

    if (strpos($command, 'unknowndir') !== false) {
        return false;
    }

    return [
        0 => '211-Status of somewhere/folder/dummy.txt:',
        1 => ' -rw-r--r-- 1 ftp ftp 0 Nov 24 13:59 somewhere/folder/dummy.txt',
        2 => '211 End of status'
    ];
}

function ftp_rawlist($connection, $directory)
{
    $directory = str_replace("-A ", "", $directory);

    if (getenv('FTP_CLOSE_THROW') === 'DISCONNECT_CATCH') {
        throw new ErrorException('ftp_rawlist');
    }

    if (getenv('FTP_CLOSE_THROW') === 'DISCONNECT_RETHROW') {
        throw new ErrorException('does not contain the correct message');
    }

    if (strpos($directory, 'recurse.manually/recurse.folder') !== false) {
        return [
            '-rw-r--r--   1 ftp      ftp           409 Aug 19 09:01 file1.txt',
        ];
    }

    if (strpos($directory, 'recurse.manually') !== false) {
        return [
            'drwxr-xr-x   2 ftp      ftp          4096 Nov 24 13:59 recurse.folder',
        ];
    }

    if (strpos($directory, 'recurse.folder') !== false) {
        return false;
    }

    if (strpos($directory, 'fail.rawlist') !== false) {
        return false;
    }

    if ($directory === 'not.found') {
        return false;
    }

    if ($directory === 'windows.not.found') {
        return ["File not found"];
    }

    if (strpos($directory, 'file1.txt') !== false) {
        return [
            '-rw-r--r--   1 ftp      ftp           409 Aug 19 09:01 file1.txt',
        ];
    }

    if ($directory === '0') {
        return [
            '-rw-r--r--   1 ftp      ftp           409 Aug 19 09:01 0',
        ];
    }

    if (strpos($directory, 'file2.txt') !== false) {
        return [
            '05-23-15  12:09PM                  684 file2.txt',
        ];
    }

    if (strpos($directory, 'file3.txt') !== false) {
        return [
            '06-09-2016  12:09PM                  684 file3.txt',
        ];
    }

    if (strpos($directory, 'file4.txt') !== false) {
        return [
            '2016-05-23  12:09PM                  684 file4.txt',
        ];
    }

    if (strpos($directory, 'file5WithPadding.txt') !== false) {
        return [
            '   2016-05-23  12:09PM                  685 file5WithPadding.txt',
        ];
    }

    if (strpos($directory, 'dir1') !== false) {
        return [
            '2015-05-23  12:09       <DIR>          dir1',
        ];
    }

    if (strpos($directory, 'rmdir.nested.fail') !== false) {
        return [
            'drwxr-xr-x   2 ftp      ftp          4096 Oct 13  2012 .',
            'drwxr-xr-x   4 ftp      ftp          4096 Nov 24 13:58 ..',
            '-rw-r--r--   1 ftp      ftp           409 Oct 13  2012 rm.fail.txt',
        ];
    }

    if (strpos($directory, 'lastfiledir') !== false) {
        return [
            'drwxr-xr-x   2 ftp      ftp          4096 Feb  6  2012 .',
            'drwxr-xr-x   4 ftp      ftp          4096 Feb  6 13:58 ..',
            '-rw-r--r--   1 ftp      ftp           409 Aug 19 09:01 file1.txt',
            '-rw-r--r--   1 ftp      ftp           409 Aug 14 09:01 file2.txt',
            '-rw-r--r--   1 ftp      ftp           409 Feb  6 10:06 file3.txt',
            '-rw-r--r--   1 ftp      ftp           409 Mar 20  2014 file4.txt',
        ];
    }

    if (strpos($directory, 'spaced.files') !== false) {
        return [
            'drwxr-xr-x   2 ftp      ftp          4096 Feb  6  2012 .',
            'drwxr-xr-x   4 ftp      ftp          4096 Feb  6 13:58 ..',
            '-rw-r--r--   1 ftp      ftp           409 Aug 19 09:01  file1.txt',

        ];
    }

    if (strpos($directory, 'file1.with-total-line.txt') !== false) {
        return [
            'total 1',
            '-rw-r--r--   1 ftp      ftp           409 Aug 19 09:01 file1.txt',
        ];
    }

    if (strpos($directory, 'rawlist-total-0.txt') !== false) {
        return [
            'total 0',
        ];
    }

    if (strpos($directory, 'file1.with-invalid-line.txt') !== false) {
        return [
            'invalid line',
            '-rw-r--r--   1 ftp      ftp           409 Aug 19 09:01 file1.txt',
        ];
    }

    if (strpos($directory, 'some.nested/rmdir.fail') !== false || strpos($directory, 'somewhere/cgi-bin') !== false) {
        return [
            'drwxr-xr-x   2 ftp      ftp          4096 Oct 13  2012 .',
            'drwxr-xr-x   4 ftp      ftp          4096 Nov 24 13:58 ..',
        ];
    }

    if (strpos($directory, 'some.nested') !== false) {
        return ['drwxr-xr-x   1 ftp      ftp           409 Aug 19 09:01 rmdir.fail'];
    }

    if (strpos($directory, 'somewhere/folder') !== false) {
        return ['-rw-r--r--   1 ftp      ftp             0 Nov 24 13:59 dummy.txt'];
    }

    return [
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
    ];
}

function ftp_mdtm($connection, $path)
{
    switch ($path) {
        case 'lastfiledir/file1.txt':
            return 1408438882;
            break;

        case 'lastfiledir/file2.txt':
            return 1408006883;
            break;

        case 'lastfiledir/file3.txt':
            return 1423217165;
            break;

        case 'lastfiledir/file4.txt':
            return 1395305765;
            break;

        case 'some/file.ext':
            return 1408438882;
            break;
        default:
            return -1;
            break;
    }
}

function ftp_mkdir($connection, $dirname)
{
    return strpos($dirname, 'mkdir.fail') === false;
}

function ftp_fput($connection, $path)
{
    return strpos($path, 'write.fail') === false;
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
    return ['./some.nested'];
}

function ftp_chmod($connection, $mode, $path)
{
    return strpos($path, 'chmod.fail') === false;
}

function ftp_set_option($connection, $option, $value)
{
    putenv('USE_PASSV_ADDREESS' . $option . '=' . ($value ? 'YES' : 'NO'));

    return true;
}

class FtpTests extends TestCase
{

    protected $options = [
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
        'recurseManually' => false,
    ];

    public function setUp(): void
    {
        putenv('FTP_CLOSE_THROW=nope');
    }

    public function testInstantiable()
    {
        if ( ! defined('FTP_BINARY')) {
            $this->markTestSkipped('The FTP_BINARY constant is not defined');
        }

        $adapter = new Ftp($this->options);
        $this->assertOptionsAreRetrievable($adapter);
        $listing = $adapter->listContents('', true);
        $this->assertIsArray($listing);
        $this->assertGetterFailuresReturnFalse($adapter);
        $this->assertTrue($adapter->rename('a', 'b'));
        $this->assertTrue($adapter->delete('a'));
        $this->assertFalse($adapter->deleteDir('some.nested/rmdir.fail'));
        $this->assertFalse($adapter->deleteDir('rmdir.nested.fail'));
        $this->assertTrue($adapter->deleteDir('somewhere'));
        $result = $adapter->read('something.txt');
        $this->assertEquals('contents', $result['contents']);
        $result = $adapter->getMimetype('something.txt');
        $this->assertEquals('text/plain', $result['mimetype']);
        $this->assertFalse($adapter->createDir('some.nested/mkdir.fail', new Config()));
        $this->assertIsArray($adapter->write('unknowndir/file.txt', 'contents', new Config(['visibility' => 'public'])));
        $this->assertIsArray($adapter->writeStream('unknowndir/file.txt', tmpfile(), new Config(['visibility' => 'public'])));
        $this->assertIsArray($adapter->updateStream('unknowndir/file.txt', tmpfile(), new Config()));
        $this->assertIsArray($adapter->getTimestamp('some/file.ext'));
    }

    /**
     * @depends testInstantiable
     */
    public function testManualRecursion()
    {
        $adapter = new Ftp($this->options);
        $adapter->setRecurseManually(true);
        $result = $adapter->listContents('recurse.manually', true);
        $this->assertCount(2, $result);
        $this->assertEquals('recurse.manually/recurse.folder', $result[0]['path']);
        $this->assertEquals('recurse.manually/recurse.folder/file1.txt', $result[1]['path']);
    }

    /**
     * @depends testInstantiable
     */
    public function testDisconnect()
    {
        $adapter = new Ftp(array_merge($this->options, ['host' => 'disconnect.check']));
        $adapter->connect();
        $this->assertTrue($adapter->isConnected());
        $adapter->disconnect();
        $this->assertFalse($adapter->isConnected());
    }

    /**
     * @depends testInstantiable
     */
    public function testIsConnectedTimeout()
    {
        putenv('FTP_CLOSE_THROW=DISCONNECT_CATCH');

        $adapter = new Ftp(array_merge($this->options, ['host' => 'disconnect.check']));
        $adapter->connect();
        $this->assertFalse($adapter->isConnected());
    }

    /**
     * @depends testInstantiable
     */
    public function testIgnorePassiveAddress()
    {
        if ( ! defined('FTP_USEPASVADDRESS')) {
            define('FTP_USEPASVADDRESS', 2);
        }

        $this->assertFalse(getenv('USE_PASSV_ADDREESS' . FTP_USEPASVADDRESS));
        $adapter = new Ftp(array_merge($this->options, ['ignorePassiveAddress' => true]));
        $adapter->connect();
        $this->assertEquals('NO', getenv('USE_PASSV_ADDREESS' . FTP_USEPASVADDRESS));
    }

    /**
     * @depends testInstantiable
     */
    public function testGetMetadataForRoot()
    {
        $adapter = new Ftp($this->options);
        $metadata = $adapter->getMetadata('');
        $expected = ['type' => 'dir', 'path' => ''];
        $this->assertEquals($expected, $metadata);
    }

    /**
     * @depends testInstantiable
     */
    public function testGetMetadata()
    {
        $adapter = new Ftp($this->options);
        $metadata = $adapter->getMetadata('file1.txt');
        $this->assertIsArray($metadata);
        $this->assertEquals('file', $metadata['type']);
        $this->assertEquals('file1.txt', $metadata['path']);
    }

    /**
     * @depends testInstantiable
     */
    public function testHasWithTotalZero()
    {
        $adapter = new Ftp($this->options);
        $this->assertFalse($adapter->getMetadata('rawlist-total-0.txt'));
    }

    /**
     * @depends testInstantiable
     */
    public function testGetMetadataForRootFileNamedZero()
    {
        $adapter = new Ftp($this->options);
        $metadata = $adapter->getMetadata('0');
        $this->assertIsArray($metadata);
        $this->assertEquals('file', $metadata['type']);
        $this->assertEquals('0', $metadata['path']);
    }

    /**
     * @depends testInstantiable
     */
    public function testGetMetadataIgnoresInvalidTotalLine()
    {
        $adapter = new Ftp($this->options);
        $metadata = $adapter->getMetadata('file1.with-total-line.txt');
        $this->assertEquals('file1.txt', $metadata['path']);
    }

    /**
     * @depends testInstantiable
     */
    public function testGetWindowsMetadata()
    {
        $adapter = new Ftp($this->options);
        $metadata = $adapter->getMetadata('file2.txt');
        $this->assertIsArray($metadata);
        $this->assertEquals('file', $metadata['type']);
        $this->assertEquals('file2.txt', $metadata['path']);
        $this->assertEquals(1432382940, $metadata['timestamp']);
        $this->assertEquals('public', $metadata['visibility']);
        $this->assertEquals(684, $metadata['size']);

        $metadata = $adapter->getMetadata('file3.txt');
        $this->assertIsArray($metadata);
        $this->assertEquals('file', $metadata['type']);
        $this->assertEquals('file3.txt', $metadata['path']);
        $this->assertEquals(1473163740, $metadata['timestamp']);
        $this->assertEquals('public', $metadata['visibility']);
        $this->assertEquals(684, $metadata['size']);

        $metadata = $adapter->getMetadata('file4.txt');
        $this->assertIsArray($metadata);
        $this->assertEquals('file', $metadata['type']);
        $this->assertEquals('file4.txt', $metadata['path']);
        $this->assertEquals(1464005340, $metadata['timestamp']);
        $this->assertEquals('public', $metadata['visibility']);
        $this->assertEquals(684, $metadata['size']);

        $metadata = $adapter->getMetadata('file5WithPadding.txt');
        $this->assertIsArray($metadata);
        $this->assertEquals('file', $metadata['type']);
        $this->assertEquals('file5WithPadding.txt', $metadata['path']);
        $this->assertEquals(1464005340, $metadata['timestamp']);
        $this->assertEquals('public', $metadata['visibility']);
        $this->assertEquals(685, $metadata['size']);

        $metadata = $adapter->getMetadata('dir1');
        $this->assertEquals('dir', $metadata['type']);
        $this->assertEquals('dir1', $metadata['path']);
        $this->assertEquals(1432382940, $metadata['timestamp']);
    }

    /**
     * @depends testInstantiable
     *
     * Some Windows FTP server return a 500 error with the message "File not found" instead of false
     * when calling ftp_rawlist() on invalid dir
     */
    public function testFileNotFoundWindowMetadata()
    {
        $adapter = new Ftp($this->options);
        $metadata = $adapter->getMetadata('windows.not.found');
        $this->assertFalse($metadata);
    }

    /**
     * @depends testInstantiable
     */
    public function testFileNotFoundWindows()
    {
        $adapter = new Ftp($this->options);
        $this->assertFalse($adapter->has('windows.not.found'));
        $this->assertFalse($adapter->getVisibility('windows.not.found'));
        $this->assertFalse($adapter->getSize('windows.not.found'));
        $this->assertFalse($adapter->getMimetype('windows.not.found'));
        $this->assertFalse($adapter->getTimestamp('windows.not.found'));
        $this->assertFalse($adapter->write('write.fail', 'contents', new Config()));
        $this->assertFalse($adapter->writeStream('write.fail', tmpfile(), new Config()));
        $this->assertFalse($adapter->update('write.fail', 'contents', new Config()));
        $this->assertFalse($adapter->setVisibility('chmod.fail', 'private'));
    }


    /**
     * @depends testInstantiable
     */
    public function testGetLastFile()
    {
        $adapter = new Ftp($this->options);

        $listing = $adapter->listContents('lastfiledir');
        $last_modified_file = reset($listing);
        foreach ($listing as $file) {
            $file_time = $adapter->getTimestamp($file['path'])['timestamp'];
            $last_file_time = $adapter->getTimestamp($last_modified_file['path'])['timestamp'];

            if ($last_file_time < $file_time) {
                $last_modified_file = $file;
            }
        }

        $this->assertEquals('lastfiledir/file3.txt', $last_modified_file['path']);
    }

    /**
     * @depends testInstantiable
     */
    public function testListDirWithFileWithLeadingSpace()
    {
        $adapter = new Ftp($this->options);
        $listing = $adapter->listContents('spaced.files');
        $file = array_pop($listing);

        $this->assertEquals('spaced.files/ file1.txt', $file['path']);
    }

    /**
     * @depends testInstantiable
     */
    public function testListingNotEmpty()
    {
        $adapter = new Ftp($this->options);

        $listing = $adapter->listContents('');

        $this->assertNotEmpty($listing);
    }

    public function expectedUnixListings()
    {
        return [
            [
                /*$directory=*/ '',
                /*$recursive=*/ false,
                /*$enableTimestamps=*/ true,
                /*'expectedListing'=>*/ [
                    [
                        'type' => 'dir',
                        'path' => 'cgi-bin',
                        'timestamp' => 1350086400,
                    ],
                    [
                        'type' => 'dir',
                        'path' => 'folder',
                        'timestamp' => DateTime::createFromFormat('M d H:i', 'Nov 24 13:59')->getTimestamp(),
                    ],
                    [
                        'type' => 'file',
                        'path' => 'index.html',
                        'visibility' => 'public',
                        'size' => 409,
                        'timestamp' => 1350086400,
                    ],
                    [
                        'type' => 'file',
                        'path' => 'somewhere/folder/dummy.txt',
                        'visibility' => 'public',
                        'size' => 0,
                        'timestamp' => DateTime::createFromFormat('M d H:i', 'Nov 24 13:59')->getTimestamp(),
                    ],
                ]
            ],
            [
                /*$directory=*/ '',
                /*$recursive=*/ true,
                /*$enableTimestamps=*/ true,
                /*'expectedListing'=>*/ [
                    [
                        'type' => 'dir',
                        'path' => 'cgi-bin',
                        'timestamp' => 1350086400,
                    ],
                    [
                        'type' => 'dir',
                        'path' => 'folder',
                        'timestamp' => DateTime::createFromFormat('M d H:i', 'Nov 24 13:59')->getTimestamp(),
                    ],
                    [
                        'type' => 'file',
                        'path' => 'index.html',
                        'visibility' => 'public',
                        'size' => 409,
                        'timestamp' => 1350086400,
                    ],
                    [
                        'type' => 'file',
                        'path' => 'somewhere/folder/dummy.txt',
                        'visibility' => 'public',
                        'size' => 0,
                        'timestamp' => DateTime::createFromFormat('M d H:i', 'Nov 24 13:59')->getTimestamp(),
                    ],
                ]
            ],
            [
                /*$directory=*/ 'lastfiledir',
                /*$recursive=*/ true,
                /*$enableTimestamps=*/ true,
                /*'expectedListing'=>*/ [
                    [
                        'type' => 'file',
                        'path' => 'lastfiledir/file1.txt',
                        'visibility' => 'public',
                        'size' => 409,
                        'timestamp' => DateTime::createFromFormat('M d H:i', 'Aug 19 09:01')->getTimestamp(),
                    ],
                    [
                        'type' => 'file',
                        'path' => 'lastfiledir/file2.txt',
                        'visibility' => 'public',
                        'size' => 409,
                        'timestamp' => DateTime::createFromFormat('M d H:i', 'Aug 14 09:01')->getTimestamp(),
                    ],
                    [
                        'type' => 'file',
                        'path' => 'lastfiledir/file3.txt',
                        'visibility' => 'public',
                        'size' => 409,
                        'timestamp' => DateTime::createFromFormat('M d H:i', 'Feb 6 10:06')->getTimestamp(),
                    ],
                    [
                        'type' => 'file',
                        'path' => 'lastfiledir/file4.txt',
                        'visibility' => 'public',
                        'size' => 409,
                        'timestamp' => 1395273600,
                    ],
                ]
            ],
            [
                /*$directory=*/ 'lastfiledir',
                /*$recursive=*/ true,
                /*$enableTimestamps=*/ false,
                /*'expectedListing'=>*/ [
                    [
                        'type' => 'file',
                        'path' => 'lastfiledir/file1.txt',
                        'visibility' => 'public',
                        'size' => 409,
                    ],
                    [
                        'type' => 'file',
                        'path' => 'lastfiledir/file2.txt',
                        'visibility' => 'public',
                        'size' => 409,
                    ],
                    [
                        'type' => 'file',
                        'path' => 'lastfiledir/file3.txt',
                        'visibility' => 'public',
                        'size' => 409,
                    ],
                    [
                        'type' => 'file',
                        'path' => 'lastfiledir/file4.txt',
                        'visibility' => 'public',
                        'size' => 409,
                    ],
                ]
            ]
        ];
    }

    /**
     * @depends testInstantiable
     * @dataProvider expectedUnixListings
     */
    public function testListingFromUnixFormat($directory, $recursive, $enableTimestamps, $expectedListing)
    {
        $adapter = new Ftp($this->options += ['enableTimestampsOnUnixListings' => $enableTimestamps]);
        $listing = $adapter->listContents($directory, $recursive);
        $this->assertEquals($listing, $expectedListing);
    }

    /**
     * @depends testInstantiable
     */
    public function testConnectFail()
    {
        $this->expectException(RuntimeException::class);
        $adapter = new Ftp(['host' => 'fail.me', 'ssl' => false, 'transferMode' => FTP_BINARY]);
        $adapter->connect();
    }

    /**
     * @depends testInstantiable
     */
    public function testRawlistFail()
    {
        $adapter = new Ftp($this->options);
        $result = $adapter->listContents('fail.rawlist');
        $this->assertEquals([], $result);
    }

    /**
     * @depends testInstantiable
     */
    public function testConnectFailSsl()
    {
        $this->expectException(RuntimeException::class);
        $adapter = new Ftp(['host' => 'fail.me', 'ssl' => true]);
        $adapter->connect();
    }

    /**
     * @depends testInstantiable
     */
    public function testLoginFailSsl()
    {
        if (PHP_MAJOR_VERSION > 7) {
            $this->markTestSkipped('PHP 8.0.0 does not accept non resource arguments.');
        }

        $this->expectException(RuntimeException::class);
        $adapter = new Ftp(['host' => 'login.fail', 'ssl' => true]);
        $adapter->connect();
    }

    /**
     * @depends testInstantiable
     */
    public function testRootFailSsl()
    {
        $this->expectException(RuntimeException::class);
        $adapter = new Ftp(['host' => 'chdir.fail', 'ssl' => true, 'root' => 'somewhere']);
        $adapter->connect();
    }

    /**
     * @depends testInstantiable
     */
    public function testPassiveFailSsl()
    {
        $this->expectException(RuntimeException::class);
        $adapter = new Ftp(['host' => 'pasv.fail', 'ssl' => true, 'root' => 'somewhere']);
        $adapter->connect();
    }

    /**
     * @depends testInstantiable
     */
    public function testItReconnects()
    {
        $adapter = new Ftp(['host' => 'reconnect.me', 'ssl' => true, 'root' => 'somewhere']);
        $this->assertFalse($adapter->isConnected());
        $this->assertNotNull($adapter->getConnection());
    }

    /**
     * @depends testInstantiable
     */
    public function testItCanSetSystemType()
    {
        $adapter = new Ftp($this->options);
        $this->assertNull($adapter->getSystemType());
        $adapter->setSystemType('unix');
        $this->assertEquals('unix', $adapter->getSystemType());
    }

    /**
     * @depends testInstantiable
     */
    public function testItThrowsAnExceptionWhenAnInvalidSystemTypeIsSet()
    {
        $this->expectException(NotSupportedException::class);
        $adapter = new Ftp($this->options + ['systemType' => 'unknown']);
        $adapter->listContents();
    }

    /**
     * @depends testInstantiable
     */
    public function testItThrowsAnExceptionWhenAnInvalidUnixListingIsFound()
    {
        $this->expectException(RuntimeException::class);
        $adapter = new Ftp($this->options + ['systemType' => 'unix']);
        $adapter->getMetadata('file1.with-invalid-line.txt');
    }

    /**
     * @depends testInstantiable
     */
    public function testReadFailure()
    {
        $adapter = new Ftp($this->options + ['systemType' => 'unix']);
        $this->assertFalse($adapter->read('not.found'));
    }

    /**
     * @depends testInstantiable
     */
    public function testItThrowsAnExceptionWhenAnInvalidWindowsListingIsFound()
    {
        $this->expectException(RuntimeException::class);
        $adapter = new Ftp($this->options + ['systemType' => 'windows']);
        $metadata = $adapter->getMetadata('file1.with-invalid-line.txt');
        $this->assertEquals('file1.txt', $metadata['path']);
    }

    /**
     * @depends testInstantiable
     */
    public function testItSetUtf8Mode()
    {
        $adapter = new Ftp($this->options + ['utf8' => true]);
        $adapter->setUtf8(true);
        $this->assertNull($adapter->connect());
    }

        /**
     * @depends testInstantiable
     */
    public function testItSetUtf8ModeWhenAlreadySetByServer()
    {
        $adapter = new Ftp(['host' => 'utf8.alreadyActive', 'utf8' => true]);
        $adapter->setUtf8(true);
        $this->assertNull($adapter->connect());
    }

    /**
     * @depends testInstantiable
     */
    public function testItThrowsAnExceptionWhenItCouldNotSetUtf8ModeForConnection()
    {
        $this->expectException(RuntimeException::class);
        $adapter = new Ftp(['host' => 'utf8.fail', 'utf8' => true]);
        $adapter->setUtf8(true);
        $adapter->connect();
    }

    /**
     * @param $adapter
     */
    protected function assertOptionsAreRetrievable($adapter)
    {
        $this->assertEquals('example.org', $adapter->getHost());
        $this->assertEquals(40, $adapter->getPort());
        $this->assertEquals(35, $adapter->getTimeout());
        $this->assertEquals('/somewhere/', $adapter->getRoot());
        $this->assertEquals(0777, $adapter->getPermPublic());
        $this->assertEquals(0000, $adapter->getPermPrivate());
        $this->assertEquals('user', $adapter->getUsername());
        $this->assertEquals('password', $adapter->getPassword());
    }

    /**
     * @param $adapter
     */
    protected function assertGetterFailuresReturnFalse($adapter)
    {
        $this->assertFalse($adapter->has('not.found'));
        $this->assertFalse($adapter->getVisibility('not.found'));
        $this->assertFalse($adapter->getSize('not.found'));
        $this->assertFalse($adapter->getMimetype('not.found'));
        $this->assertFalse($adapter->getTimestamp('not.found'));
        $this->assertFalse($adapter->write('write.fail', 'contents', new Config()));
        $this->assertFalse($adapter->writeStream('write.fail', tmpfile(), new Config()));
        $this->assertFalse($adapter->update('write.fail', 'contents', new Config()));
        $this->assertFalse($adapter->setVisibility('chmod.fail', 'private'));
    }
}
