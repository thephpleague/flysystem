<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;
use RuntimeException;

class Ftp extends AbstractFtpAdapter
{
    protected $configurable = array('host', 'port', 'username', 'password', 'ssl', 'timeout', 'root', 'permPrivate', 'permPublic', 'passive');

    /**
     * Returns if SSL is enabled
     *
     * @return bool
     */
    public function getSsl()
    {
        return $this->ssl;
    }

    /**
     * Set if Ssl is enabled
     *
     * @param bool $ssl
     * @return \Flysystem\Adapter\Ftp
     */
    public function setSsl($ssl)
    {
        $this->ssl = (bool) $ssl;

        return $this;
    }

    /**
     * Returns if passive mode will be used
     *
     * @return bool
     */
    public function getPassive()
    {
        return $this->passive;
    }

    /**
     * Set if passive mode should be used
     *
     * @param bool $passive
     */
    public function setPassive($passive = true)
    {
        $this->passive = $passive;
    }

    /**
     * Returns the root folder to work from
     *
     * @return string
     */
    public function getRoot()
    {
        return $this->root;
    }

    public function connect()
    {
        if ($this->ssl) {
            $this->connection = ftp_ssl_connect($this->getHost(), $this->getPort(), $this->getTimeout());
        } else {
            $this->connection = ftp_connect($this->getHost(), $this->getPort(), $this->getTimeout());
        }

        if ( ! $this->connection) {
            throw new RuntimeException('Could not connect to host: ' . $this->getHost() . ', port:' . $this->getPort());
        }

        $this->login();
        $this->setConnectionPassiveMode();
        $this->setConnectionRoot();
    }

    protected function setConnectionPassiveMode()
    {
        if ( ! ftp_pasv($this->getConnection(), $this->getPassive())) {
            throw new RuntimeException('Could not set passive mode for connection: ' . $this->getHost() . '::' . $this->getPort());
        }
    }

    protected function setConnectionRoot()
    {
        $connection = $this->getConnection();

        if ($this->root && ! ftp_chdir($connection, $this->getRoot())) {
            throw new RuntimeException('Root is invalid or does not exist: ' . $this->getRoot());
        }

        // Store absolute path for further reference.
        // This is needed when creating directories and
        // initial root was a relative path, else the root
        // would be relative to the chdir'd path.
        $this->root = ftp_pwd($connection);
    }

    protected function login()
    {
        if ( ! @ftp_login($this->getConnection(), $this->getUsername(), $this->getPassword())) {
            throw new RuntimeException('Could not login with connection: ' . $this->getHost() . '::' . $this->getPort() . ', username: ' . $this->getUsername());
        }
    }

    public function disconnect()
    {
        if ($this->connection) {
            ftp_close($this->connection);
        }

        $this->connection = null;
    }

    public function write($path, $contents, $config = null)
    {
        $this->ensureDirectory(Util::dirname($path));
        $mimetype = Util::guessMimeType($path, $contents);
        $config = Util::ensureConfig($config);
        $stream = $contents;

        if ( ! is_resource($stream)) {
            $stream = tmpfile();
            fwrite($stream, $contents);
            rewind($stream);
        }

        $result = ftp_fput($this->getConnection(), $path, $stream, FTP_BINARY);
        fclose($stream);

        if ( ! $result) {
            return false;
        }

        if ($visibility = $config->get('visibility')) {
            $this->setVisibility($path, $visibility);
        }

        return compact('path', 'contents', 'mimetype', 'visibility');
    }

    public function writeStream($path, $resource, $config = null)
    {
        $this->ensureDirectory(Util::dirname($path));
        $config = Util::ensureConfig($config);

        if ( ! ftp_fput($this->getConnection(), $path, $resource, FTP_BINARY)) {
            return false;
        }

        if ($visibility = $config->get('visibility')) {
            $this->setVisibility($path, $visibility);
        }

        return compact('path', 'visibility');
    }

    public function update($path, $contents, $config = null)
    {
        return $this->write($path, $contents, $config);
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
        $connection = $this->getConnection();
        $contents = array_reverse($this->listDirectoryContents($dirname));

        foreach ($contents as $object) {
            if ($object['type'] === 'file') {
                ftp_delete($connection, $object['path']);
            } else {
                ftp_rmdir($connection, $object['path']);
            }
        }

        ftp_rmdir($connection, $dirname);
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
        $result = false;
        $connection = $this->getConnection();
        $directories = explode('/', $dirname);

        while ($directory = array_shift($directories)) {
            $result = $this->createActualDirectory($directory, $connection);

            if ( ! $result) {
                break;
            }

            ftp_chdir($connection, $directory);
        }

        $this->setConnectionRoot();

        if ( ! $result) {
            return false;
        }

        return array('path' => $dirname);
    }

    protected function createActualDirectory($directory, $connection)
    {
        // List the current directory
        $listing = ftp_nlist($connection, '.');

        $listing = array_map(function ($item) {
            return ltrim($item, './');
        }, $listing);

        if (in_array($directory, $listing)) {
            return true;
        }

        return ftp_mkdir($connection, $directory);
    }

    public function getMetadata($path)
    {
        if (empty($path) ||  ! ($object = ftp_raw($this->getConnection(), 'STAT ' . $path)) || count($object) < 3) {
            return false;
        }

        return $this->normalizeObject($object[1], '');
    }

    public function getMimetype($path)
    {
        if ( ! $metadata = $this->read($path)) {
            return false;
        }

        $metadata['mimetype'] = Util::guessMimeType($path, $metadata['contents']);

        return $metadata;
    }

    public function read($path)
    {
        if ( ! $object = $this->readStream($path)) {
            return false;
        }

        $object['contents'] = stream_get_contents($object['stream']);
        fclose($object['stream']);
        unset($object['stream']);

        return $object;
    }

    public function readStream($path)
    {
        $stream = fopen('php://temp', 'w+');
        $result = ftp_fget($this->getConnection(), $stream, $path, FTP_BINARY);
        rewind($stream);

        if ( ! $result) {
            fclose($stream);
            return false;
        }

        return compact('stream');
    }

    public function setVisibility($path, $visibility)
    {
        $mode = $visibility === AdapterInterface::VISIBILITY_PUBLIC ? $this->getPermPublic() : $this->getPermPrivate();

        if ( ! ftp_chmod($this->getConnection(), $mode, $path)) {
            return false;
        }

        return compact('visibility');
    }

    protected function listDirectoryContents($directory, $recursive = true)
    {
        $listing = ftp_rawlist($this->getConnection(), $directory, $recursive);

        if ($listing === false) {
            return array();
        }

        return $this->normalizeListing($listing, $directory);
    }
}
