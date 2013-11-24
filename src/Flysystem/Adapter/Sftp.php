<?php

namespace Flysystem\Adapter;

use Net_SFTP;
use Crypt_RSA;
use Flysystem\AdapterInterface;
use Flysystem\Util;
use LogicException;

class Sftp extends AbstractFtpAdapter
{
    protected $port = 22;
    protected $privatekey;
    protected $configurable = array('host', 'port', 'username', 'password', 'timeout', 'root', 'privateKey', 'permPrivate', 'permPublic');
    protected $statMap = array('mtime' => 'timestamp', 'size' => 'size');

    protected function prefix($path)
    {
        return $this->root.ltrim($path, $this->separator);
    }

    public function setPrivateKey($key)
    {
        $this->privatekey = $key;

        return $this;
    }

    public function connect()
    {
        $this->connection = new Net_SFTP($this->host, $this->port, $this->timeout);

        if ( ! $this->connection) {
            throw new LogicException('Could not connect to host: '.$this->host);
        }

        $this->login();
        $this->setConnectionRoot();
    }

    protected function login()
    {
        if ( ! $this->connection->login($this->username, $this->getPassword())) {
            throw new LogicException('Could not login with username: '.$this->username.', host: '.$this->host);
        }
    }

    protected function setConnectionRoot()
    {
        if ($this->root) {
            $this->connection->chdir($this->root);
        }
    }

    public function getPassword()
    {
        if ($this->privatekey) {
            return $this->getPrivateKey();
        }

        return $this->password;
    }

    public function getPrivateKey()
    {
        if (is_file($this->privatekey)) {
            $this->privatekey = file_get_contents($this->privatekey);
        }

        $key = new Crypt_RSA();

        if ($this->password) {
            $key->setPassword($this->password);
        }

        $key->loadKey($this->privatekey);

        return $key;
    }

    protected function listDirectoryContents($directory, $recursive = true)
    {
        $connection = $this->getConnection();
        $listing = $connection->exec('cd '.$this->root.$directory.' && ls -la'.($recursive ? 'R' : ''));
        $listing = explode(PHP_EOL, trim($listing));
        array_shift($listing);

        return $this->normalizeListing($listing, $directory);
    }

    public function disconnect()
    {
        $this->connection = null;
    }

    public function write($path, $contents, $visibility = null)
    {
        $connection = $this->getConnection();
        $this->ensureDirectory(Util::dirname($path));

        if ( ! $connection->put($path, $contents, NET_SFTP_STRING)) {
            return false;
        }

        if ($visibility) {
            $this->setVisibility($path, $visibility);
        }

        return compact('contents', 'visibility');
    }

    public function read($path)
    {
        $connection = $this->getConnection();

        if (($contents = $connection->get($path)) === false) {
            return false;
        }

        return compact('contents');
    }

    public function update($path, $contents)
    {
        return $this->write($path, $contents);
    }

    public function delete($path)
    {
        $connection = $this->getConnection();

        return $connection->delete($path);
    }

    public function rename($path, $newpath)
    {
        $connection = $this->getConnection();

        return $connection->rename($path, $newpath);
    }

    public function deleteDir($dirname)
    {
        $connection = $this->getConnection();

        return $connection->delete($dirname, true);
    }

    public function has($path)
    {
        return $this->getMetadata($path);
    }

    public function getMetadata($path)
    {
        $connection = $this->getConnection();
        $info = $connection->stat($path);
        $result = Util::map($info, $this->statMap);
        $result['type'] = $info['type'] === NET_SFTP_TYPE_DIRECTORY ? 'dir' : 'file';
        $result['visibility'] = $info['permissions'] & $this->permPublic ? 'public' : 'private';

        return $result;
    }

    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    public function getMimetype($path)
    {
        if ( ! $data = $this->read($path)) {
            return false;
        }

        $data['mimetype'] = Util::contentMimetype($data['contents']);

        return $data;
    }

    public function createDir($dirname)
    {
        $connection = $this->getConnection();

        if ( ! $connection->mkdir($dirname, 0744, true)) {
            return false;
        }

        return array('path' => $dirname);
    }

    public function getVisibility($path)
    {
        return $this->getMetadata($path);
    }

    public function setVisibility($path, $visibility)
    {
        $visibility = ucfirst($visibility);

        if ( ! isset($this->{'perm'.$visibility})) {
            throw new \InvalidArgumentException('Unknown visibility: '.$visibility);
        }

        $connection = $this->getConnection();

        return $connection->chmod($this->{'perm'.$visibility}, $path);
    }
}
