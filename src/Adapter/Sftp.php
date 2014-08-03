<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Config;
use Net_SFTP;
use Crypt_RSA;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Util;
use LogicException;
use InvalidArgumentException;

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

    public function setNetSftpConnection(Net_SFTP $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    public function connect()
    {
        $this->connection = $this->connection ?: new Net_SFTP($this->host, $this->port, $this->timeout);
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
        $result = array();
        $connection = $this->getConnection();
        $location = $this->prefix($directory);
        $listing = $connection->rawlist($location);

        if ($listing === false) {
            return array();
        }

        foreach ($listing as $filename => $object) {
            if (in_array($filename, array('.', '..'))) {
                continue;
            }

            $path = empty($directory) ? $filename : ($directory . DIRECTORY_SEPARATOR . $filename);
            $result[] = $this->normalizeListingObject($path, $object);

            if ($recursive && $object['type'] === NET_SFTP_TYPE_DIRECTORY) {
                $result = array_merge($result, $this->listDirectoryContents($path));
            }
        }

        return $result;
    }

    protected function normalizeListingObject($path, $object)
    {
        $permissions = $this->normalizePermissions($object['permissions']);

        return array(
            'path' => $path,
            'size' => $object['size'],
            'timestamp' => $object['mtime'],
            'type' => ($object['type'] === 1 ? 'file' : 'dir'),
            'visibility' => $permissions & 0044 ? AdapterInterface::VISIBILITY_PUBLIC : AdapterInterface::VISIBILITY_PRIVATE,
        );
    }

    public function disconnect()
    {
        $this->connection = null;
    }

    public function write($path, $contents, $config = null)
    {
        $connection = $this->getConnection();
        $this->ensureDirectory(Util::dirname($path));
        $config = Util::ensureConfig($config);

        if ( ! $connection->put($path, $contents, NET_SFTP_STRING)) {
            return false;
        }

        if ($config && $visibility = $config->get('visibility'))
            $this->setVisibility($path, $visibility);

        return compact('contents', 'visibility', 'path');
    }

    public function read($path)
    {
        $connection = $this->getConnection();

        if (($contents = $connection->get($path)) === false) {
            return false;
        }

        return compact('contents');
    }

    public function update($path, $contents, $config = null)
    {
        return $this->write($path, $contents, $config);
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

        if ($info === false) {
            return false;
        }

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

        $data['mimetype'] = Util::guessMimeType($path, $data['contents']);

        return $data;
    }

    /**
     * Create a directory
     *
     * @param   string       $dirname directory name
     * @param   array|Config $options
     *
     * @return  bool
     */
    public function createDir($dirname, $options = null)
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
            throw new InvalidArgumentException('Unknown visibility: '.$visibility);
        }

        $connection = $this->getConnection();

        return $connection->chmod($this->{'perm'.$visibility}, $path);
    }
}
