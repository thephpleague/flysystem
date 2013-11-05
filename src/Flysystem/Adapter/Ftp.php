<?php

namespace Flysystem\Adapter;

use Flysystem\AdapterInterface;
use Flysystem\Util;

class Ftp extends AbstractAdapter
{
    protected $connection;
    protected $host;
    protected $port = 21;
    protected $username;
    protected $password;
    protected $ssl = false;
    protected $timeout = 90;
    protected $passive = true;
    protected $separator = '/';
    protected $root;
    protected $permPublic = 0744;
    protected $permPrivate = 0000;
    protected $configurable = array('host', 'port', 'username', 'password', 'ssl', 'timeout', 'root', 'permPrivate', 'permPublic');

    public function __construct(array $config)
    {
        $this->setConfig($config);

        if (isset($config['autoconnect']) and $config['autoconnect'] === true) {
            $this->connect();
        }
    }

    public function setConfig(array $config)
    {
        foreach ($this->configurable as $setting) {
            if ( ! isset($config[$setting])) continue;
            $this->{'set'.ucfirst($setting)}($config[$setting]);
        }

        return $this;
    }

    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    public function setPermPublic($permPublic)
    {
        $this->permPublic = $permPublic;

        return $this;
    }

    public function setPermPrivate($permPrivate)
    {
        $this->permPrivate = $permPrivate;

        return $this;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    public function setSsl($ssl)
    {
        $this->ssl = $ssl;

        return $this;
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function setPassive($passive = true)
    {
        $this->passive = $passive;
    }

    public function setRoot($root)
    {
        $this->root = rtrim($root, '\\/').$this->separator;

        return $this;
    }

    public function getConnection()
    {
        if ( ! $this->connection) {
            $this->connect();
        }

        return $this->connection;
    }

    public function connect()
    {
        $connector = $this->ssl ? 'ftp_ssl_connect' : 'ftp_connect';

        if ( ! $this->connection = @$connector($this->host, $this->getPort(), $this->timeout)) {
            throw new \RuntimeException('Could not connect to host: '.$this->host.'::'.$this->getPort());
        }

        $this->login();
        $this->setConnectionPassiveMode();
        $this->setConnectionRoot();
    }

    protected function setConnectionPassiveMode()
    {
        if ( ! $result = ftp_pasv($this->connection, $this->passive)) {
            throw new \RuntimeException('Could not set passive mode for connection: '.$this->host.'::'.$this->getPort());
        }
    }

    protected function setConnectionRoot()
    {
        if ($this->root and ! ftp_chdir($this->connection, $this->root)) {
            throw new \RuntimeException('Root is invalid or does not exist: '.$this->root);
        }
    }

    protected function login()
    {
        if ( ! @ftp_login($this->connection, $this->username, $this->password)) {
            throw new \RuntimeException('Could not login with connection: '.$this->host.'::'.$this->getPort().', username: '.$this->username);
        }
    }

    public function disconnect()
    {
        if ($this->connection) {
            ftp_close($this->connection);
        }

        $this->connection = null;
    }

    public function write($path, $contents, $visibility = null)
    {
        $this->ensureDirectory(Util::dirname($path));
        $mimetype = Util::contentMimetype($contents);
        $stream = fopen('data://'.$mimetype.','.$contents, 'r');
        $result = ftp_fput($this->getConnection(), $path, $stream, FTP_BINARY);
        fclose($stream);

        if ( ! $result) {
            return false;
        }

        if ($visibility) {
            $this->setVisibility($path, $visibility);
        }

        return compact('path', 'contents', 'mimetype', 'visibility');
    }

    public function update($path, $contents)
    {
        return $this->write($path, $contents);
    }

    public function rename($path, $newpath)
    {
        return ftp_rename($this->getConnection(), $path, $newpath);
    }

    public function delete($path)
    {
        return ftp_delete($this->getConnection(), $path);
    }

    public function deleteDir($dirname)
    {
        $contents = array_reverse($this->listDirectoryContents($dirname));

        foreach ($contents as $object) {
            if ($object['type'] === 'file') {
                ftp_delete($this->getConnection(), $dirname.$this->separator.$object['path']);
            } else {
                ftp_rmdir($this->getConnection(), $dirname.$this->separator.$object['path']);
            }
        }

        ftp_rmdir($this->getConnection(), $dirname);
    }

    public function createDir($dirname)
    {
        if ( ! ftp_mkdir($this->getConnection(), $dirname)) {
            return false;
        }

        return array('path' => $dirname);
    }

    public function ensureDirectory($dirname)
    {
        if ( ! empty($dirname) and ! $this->has($dirname)) {
            $this->createDir($dirname);
        }
    }

    public function has($path)
    {
        return $this->getMetadata($path);
    }

    public function getMetadata($path)
    {
        if ( ! $object = ftp_raw($this->getConnection(), 'STAT '.$path) or count($object) < 3) {
            return false;
        }

        $dirname = dirname($path);
        if ($dirname === '.') $dirname = '';

        return $this->normalizeObject($object[1], $dirname);
    }

    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    public function getMimetype($path)
    {
        if ( ! $metadata = $this->read($path)) {
            return false;
        }

        $metadata['mimetype'] = Util::contentMimetype($metadata['contents']);

        return $metadata;
    }

    public function read($path)
    {
        $stream = fopen('php://temp', 'w+');
        $result = ftp_fget($this->getConnection(), $stream, $path, FTP_BINARY);

        if ( ! $result) {
            fclose($stream);
            return false;
        }

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        return compact('contents');
    }

    public function setVisibility($path, $visibility)
    {
        $mode = $visibility === AdapterInterface::VISIBILITY_PUBLIC ? 0644 : 0000;

        if ( ! ftp_chmod($this->getConnection(), $mode, $path)) {
            return false;
        }

        return compact('visibility');
    }

    public function getVisibility($path)
    {
        return $this->getMetadata($path);
    }

    public function listContents()
    {
        return $this->listDirectoryContents('');
    }

    protected function listDirectoryContents($directory)
    {
        $listing = ftp_rawlist($this->getConnection(), $directory, true);

        return $this->normalizeListing($listing);
    }

    protected function normalizeListing(array $listing)
    {
        $listing = $this->removeDotDirectories($listing);

        $base = '';
        $result = array();

        while ($item = array_shift($listing))
        {
            if (preg_match('#^.*:$#', $item)) {
                $base = substr($item, 2, -1);
                continue;
            }

            $result[] = $this->normalizeObject($item, $base);
        }

        return $this->sortListing($result);
    }

    protected function sortListing(array $result)
    {
        $compare = function ($one, $two) {
            return strnatcmp($one['path'], $two['path']);
        };

        usort($result, $compare);

        return $result;
    }

    protected function normalizeObject($item, $base)
    {
        $item = preg_replace('#\s+#', ' ', trim($item));
        list ($permissions, $number, $owner, $group, $size, $month, $day, $time, $name) = explode(' ', $item, 9);

        $type = $this->detectType($permissions);
        $path = empty($base) ? $name : $base.$this->separator.$name;

        if ($type === 'dir') {
            return compact('type', 'path');
        }

        $permissions = $this->normalizePermissions($permissions);
        $visibility = $permissions & 0044 ? AdapterInterface::VISIBILITY_PUBLIC : AdapterInterface::VISIBILITY_PRIVATE;
        $size = (int) $size;
        $timestamp = strtotime($month.' '.$day.' '.$time);

        return compact('type', 'path', 'visibility', 'size', 'timestamp');
    }

    protected function detectType($permissions)
    {
        return substr($permissions, 0, 1) === 'd' ? 'dir' : 'file';
    }

    protected function normalizePermissions($permissions)
    {
        // remove the type identifier
        $permissions = substr($permissions, 1);

        // map the string rights to the numeric counterparts
        $map = array('-' => '0', 'r' => '4', 'w' => '2', 'x' => '1');
        $permissions = strtr($permissions, $map);

        // split up the permission groups
        $parts = str_split($permissions, 3);

        // convert the groups
        $mapper = function ($part) { return array_sum(str_split($part)); };

        // get the sum of the groups
        return array_sum(array_map($mapper, $parts));
    }

    protected function removeDotDirectories(array $list)
    {
        $filter = function ($line) {
            return ! empty($line) and ! preg_match('#.* \.(\.)?$|^total#', $line);
        };

        return array_filter($list, $filter);
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}