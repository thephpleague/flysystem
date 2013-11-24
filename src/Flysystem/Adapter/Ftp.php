<?php

namespace Flysystem\Adapter;

use Flysystem\AdapterInterface;
use Flysystem\Util;
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
        if ( ! $result = ftp_pasv($this->getConnection(), $this->getPassive())) {
            throw new RuntimeException('Could not set passive mode for connection: ' . $this->getHost() . '::' . $this->getPort());
        }
    }

    protected function setConnectionRoot()
    {
        if ($this->root and ! ftp_chdir($this->getConnection(), $this->getRoot())) {
            throw new RuntimeException('Root is invalid or does not exist: ' . $this->getRoot());
        }
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

    public function write($path, $contents, $visibility = null)
    {
        $this->ensureDirectory(Util::dirname($path));
        $mimetype = Util::contentMimetype($contents);
        $stream = $contents;

        if ( ! is_resource($stream)) {
            $stream = fopen('data://' . $mimetype . ',' . $contents, 'r+');
        }

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
                ftp_delete($this->getConnection(), $dirname . $this->separator . $object['path']);
            } else {
                ftp_rmdir($this->getConnection(), $dirname . $this->separator . $object['path']);
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

    public function getMetadata($path)
    {
        if ( ! $object = ftp_raw($this->getConnection(), 'STAT ' . $path) or count($object) < 3) {
            return false;
        }

        $dirname = Util::dirname($path);

        return $this->normalizeObject($object[1], '');
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

        return $this->normalizeListing($listing, $directory);
    }
}
