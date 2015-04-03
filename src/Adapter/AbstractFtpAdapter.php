<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use Net_SFTP;

abstract class AbstractFtpAdapter extends AbstractAdapter
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
    protected $permPrivate = 0700;
    protected $configurable = [];

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->setConfig($config);
    }

    /**
     * Set the config.
     *
     * @param array $config
     *
     * @return $this
     */
    public function setConfig(array $config)
    {
        foreach ($this->configurable as $setting) {
            if (! isset($config[$setting])) {
                continue;
            }

            $method = 'set'.ucfirst($setting);

            if (method_exists($this, $method)) {
                $this->$method($config[$setting]);
            }
        }

        return $this;
    }

    /**
     * Returns the host.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the host.
     *
     * @param string $host
     *
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Set the public permission value.
     *
     * @param int $permPublic
     *
     * @return $this
     */
    public function setPermPublic($permPublic)
    {
        $this->permPublic = $permPublic;

        return $this;
    }

    /**
     * Set the private permission value.
     *
     * @param int $permPrivate
     *
     * @return $this
     */
    public function setPermPrivate($permPrivate)
    {
        $this->permPrivate = $permPrivate;

        return $this;
    }

    /**
     * Returns the ftp port.
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Returns the root folder to work from.
     *
     * @return string
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Set the ftp port.
     *
     * @param int|string $port
     *
     * @return $this
     */
    public function setPort($port)
    {
        $this->port = (int) $port;

        return $this;
    }

    /**
     * Set the root folder to work from.
     *
     * @param string $root
     *
     * @return $this
     */
    public function setRoot($root)
    {
        $this->root = rtrim($root, '\\/').$this->separator;

        return $this;
    }

    /**
     * Returns the ftp username.
     *
     * @return string username
     */
    public function getUsername()
    {
        return empty($this->username) ? 'anonymous' : $this->username;
    }

    /**
     * Set ftp username.
     *
     * @param string $username
     *
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Returns the password.
     *
     * @return string password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set the ftp password.
     *
     * @param string $password
     *
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returns the amount of seconds before the connection will timeout.
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Set the amount of seconds before the connection should timeout.
     *
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int) $timeout;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        return $this->listDirectoryContents($directory, $recursive);
    }

    /**
     * Normalize a directory listing.
     *
     * @param array  $listing
     * @param string $prefix
     *
     * @return array directory listing
     */
    protected function normalizeListing(array $listing, $prefix = '')
    {
        $base = $prefix;
        $result = [];
        $listing = $this->removeDotDirectories($listing);

        while ($item = array_shift($listing)) {
            if (preg_match('#^.*:$#', $item)) {
                $base = trim($item, ':');
                continue;
            }

            $result[] = $this->normalizeObject($item, $base);
        }

        return $this->sortListing($result);
    }

    /**
     * Sort a directory listing.
     *
     * @param array $result
     *
     * @return array sorted listing
     */
    protected function sortListing(array $result)
    {
        $compare = function ($one, $two) {
            return strnatcmp($one['path'], $two['path']);
        };

        usort($result, $compare);

        return $result;
    }

    /**
     * Normalize a file entry.
     *
     * @param string $item
     * @param string $base
     *
     * @return array normalized file array
     */
    protected function normalizeObject($item, $base)
    {
        $item = preg_replace('#\s+#', ' ', trim($item), 7);
        list($permissions, /* $number */, /* $owner */, /* $group */, $size, /* $month */, /* $day */, /* $time*/, $name) = explode(' ', $item, 9);
        $type = $this->detectType($permissions);
        $path = empty($base) ? $name : $base.$this->separator.$name;

        if ($type === 'dir') {
            return compact('type', 'path');
        }

        $permissions = $this->normalizePermissions($permissions);
        $visibility = $permissions & 0044 ? AdapterInterface::VISIBILITY_PUBLIC : AdapterInterface::VISIBILITY_PRIVATE;
        $size = (int) $size;

        return compact('type', 'path', 'visibility', 'size');
    }

    /**
     * Get the file type from the permissions.
     *
     * @param string $permissions
     *
     * @return string file type
     */
    protected function detectType($permissions)
    {
        return substr($permissions, 0, 1) === 'd' ? 'dir' : 'file';
    }

    /**
     * Normalize a permissions string.
     *
     * @param string $permissions
     *
     * @return int
     */
    protected function normalizePermissions($permissions)
    {
        // remove the type identifier
        $permissions = substr($permissions, 1);

        // map the string rights to the numeric counterparts
        $map = ['-' => '0', 'r' => '4', 'w' => '2', 'x' => '1'];
        $permissions = strtr($permissions, $map);

        // split up the permission groups
        $parts = str_split($permissions, 3);

        // convert the groups
        $mapper = function ($part) {
            return array_sum(str_split($part));
        };

        // get the sum of the groups
        return array_sum(array_map($mapper, $parts));
    }

    /**
     * Filter out dot-directories.
     *
     * @param array $list
     *
     * @return array
     */
    public function removeDotDirectories(array $list)
    {
        $filter = function ($line) {
            if (! empty($line) && ! preg_match('#.* \.(\.)?$|^total#', $line)) {
                return true;
            }

            return false;
        };

        return array_filter($list, $filter);
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
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        $timestamp = ftp_mdtm($this->getConnection(), $path);

        return ($timestamp !== -1) ? ['timestamp' => $timestamp] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Ensure a directory exists.
     *
     * @param string $dirname
     */
    public function ensureDirectory($dirname)
    {
        if (! empty($dirname) && ! $this->has($dirname)) {
            $this->createDir($dirname, new Config());
        }
    }

    /**
     * @return resource|Net_SFTP
     */
    public function getConnection()
    {
        if (! $this->connection) {
            $this->connect();
        }

        return $this->connection;
    }

    /**
     * Get the public permission value.
     *
     * @return int
     */
    public function getPermPublic()
    {
        return $this->permPublic;
    }

    /**
     * Get the private permission value.
     *
     * @return int
     */
    public function getPermPrivate()
    {
        return $this->permPrivate;
    }

    /**
     * Disconnect on destruction.
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Establish a connection.
     */
    abstract public function connect();

    /**
     * Close the connection.
     */
    abstract public function disconnect();
}
