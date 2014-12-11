<?php

namespace League\Flysystem\Adapter;

use Crypt_RSA;
use InvalidArgumentException;
use League\Flysystem\Adapter\Polyfill\StreamedCopyTrait;
use League\Flysystem\Adapter\Polyfill\StreamedTrait;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;
use LogicException;
use Net_SFTP;
use RuntimeException;

class Sftp extends AbstractFtpAdapter
{
    use StreamedTrait;
    use StreamedCopyTrait;

    /**
     * @var int
     */
    protected $port = 22;

    /**
     * @var string
     */
    protected $privatekey;

    /**
     * @var array
     */
    protected $configurable = ['host', 'port', 'username', 'password', 'timeout', 'root', 'privateKey', 'permPrivate', 'permPublic'];

    /**
     * @var array
     */
    protected $statMap = ['mtime' => 'timestamp', 'size' => 'size'];

    /**
     * Prefix a path
     *
     * @param string $path
     *
     * @return string
     */
    protected function prefix($path)
    {
        return $this->root.ltrim($path, $this->separator);
    }

    /**
     * Set the private key (string or path to local file)
     *
     * @param string $key
     *
     * @return $this
     */
    public function setPrivateKey($key)
    {
        $this->privatekey = $key;

        return $this;
    }

    /**
     * Inject the Net_SFTP instance
     *
     * @param Net_SFTP $connection
     *
     * @return $this
     */
    public function setNetSftpConnection(Net_SFTP $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Connect
     *
     * @return void
     */
    public function connect()
    {
        $this->connection = $this->connection ?: new Net_SFTP($this->host, $this->port, $this->timeout);
        $this->login();
        $this->setConnectionRoot();
    }

    /**
     * Login
     *
     * @throws LogicException
     *
     * @return void
     */
    protected function login()
    {
        if (! $this->connection->login($this->username, $this->getPassword())) {
            throw new LogicException('Could not login with username: '.$this->username.', host: '.$this->host);
        }
    }

    /**
     * Set the connection root
     *
     * @return void
     */
    protected function setConnectionRoot()
    {
        $root = $this->getRoot();

        if (! $root) {
            return;
        }

        if (! $this->connection->chdir($root)) {
            throw new RuntimeException('Root is invalid or does not exist: '.$root);
        }
    }

    /**
     * Get the password, either the private key or a plain text password
     *
     * @return Crypt_RSA|string
     */
    public function getPassword()
    {
        if ($this->privatekey) {
            return $this->getPrivateKey();
        }

        return $this->password;
    }

    /**
     * Get the private get with the password or private key contents.
     *
     * @return Crypt_RSA
     */
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

    /**
     * List the contents of a directory.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array
     */
    protected function listDirectoryContents($directory, $recursive = true)
    {
        $result = [];
        $connection = $this->getConnection();
        $location = $this->prefix($directory);
        $listing = $connection->rawlist($location);

        if ($listing === false) {
            return [];
        }

        foreach ($listing as $filename => $object) {
            if (in_array($filename, ['.', '..'])) {
                continue;
            }

            $path = empty($directory) ? $filename : ($directory.DIRECTORY_SEPARATOR.$filename);
            $result[] = $this->normalizeListingObject($path, $object);

            if ($recursive && $object['type'] === NET_SFTP_TYPE_DIRECTORY) {
                $result = array_merge($result, $this->listDirectoryContents($path));
            }
        }

        return $result;
    }

    /**
     * Normalize a listing response
     *
     * @param string $path
     * @param array  $object
     *
     * @return array
     */
    protected function normalizeListingObject($path, array $object)
    {
        $permissions = $this->normalizePermissions($object['permissions']);

        return [
            'path' => $path,
            'size' => $object['size'],
            'timestamp' => $object['mtime'],
            'type' => ($object['type'] === 1 ? 'file' : 'dir'),
            'visibility' => $permissions & 0044 ? AdapterInterface::VISIBILITY_PUBLIC : AdapterInterface::VISIBILITY_PRIVATE,
        ];
    }

    /**
     * Disconnect
     *
     * @return void
     */
    public function disconnect()
    {
        $this->connection = null;
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        $connection = $this->getConnection();
        $this->ensureDirectory(Util::dirname($path));
        $config = Util::ensureConfig($config);

        if (! $connection->put($path, $contents, NET_SFTP_STRING)) {
            return false;
        }

        if ($config && $visibility = $config->get('visibility')) {
            $this->setVisibility($path, $visibility);
        }

        return compact('contents', 'visibility', 'path');
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        $connection = $this->getConnection();

        if (($contents = $connection->get($path)) === false) {
            return false;
        }

        return compact('contents');
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        $connection = $this->getConnection();

        return $connection->delete($path);
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {
        $connection = $this->getConnection();

        return $connection->rename($path, $newpath);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        $connection = $this->getConnection();

        return $connection->delete($dirname, true);
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        if (! $data = $this->read($path)) {
            return false;
        }

        $data['mimetype'] = Util::guessMimeType($path, $data['contents']);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        $connection = $this->getConnection();

        if (! $connection->mkdir($dirname, 0744, true)) {
            return false;
        }

        return ['path' => $dirname];
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility($path, $visibility)
    {
        $visibility = ucfirst($visibility);

        if (! isset($this->{'perm'.$visibility})) {
            throw new InvalidArgumentException('Unknown visibility: '.$visibility);
        }

        $connection = $this->getConnection();

        return $connection->chmod($this->{'perm'.$visibility}, $path);
    }
}
