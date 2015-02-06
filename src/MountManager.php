<?php

namespace League\Flysystem;

use InvalidArgumentException;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Plugin\PluggableTrait;
use League\Flysystem\Plugin\PluginNotFoundException;
use LogicException;
use Phly\Http\Uri;

/**
 * Class MountManager.
 *
 * Proxies methods to Filesystem (@see __call):
 *
 * If "automount" is enabled it automatically mounts required Filesystems based on provided URI
 *
 * @method AdapterInterface getAdapter($prefix)
 * @method Config getConfig($prefix)
 * @method bool has($path)
 * @method bool write($path, $contents, array $config = [])
 * @method bool writeStream($path, $resource, array $config = [])
 * @method bool put($path, $contents, $config = [])
 * @method bool putStream($path, $contents, $config = [])
 * @method string readAndDelete($path)
 * @method bool update($path, $contents, $config = [])
 * @method bool updateStream($path, $resource, $config = [])
 * @method string|false read($path)
 * @method resource|false readStream($path)
 * @method bool rename($path, $newpath)
 * @method bool delete($path)
 * @method bool deleteDir($dirname)
 * @method bool createDir($dirname, $config = [])
 * @method array listFiles($directory = '', $recursive = false)
 * @method array listPaths($directory = '', $recursive = false)
 * @method array listWith(array $keys = array(), $directory = '', $recursive = false)
 * @method array getWithMetadata($path, array $metadata)
 * @method string|false getMimetype($path)
 * @method string|false getTimestamp($path)
 * @method string|false getVisibility($path)
 * @method int|false getSize($path);
 * @method bool setVisibility($path, $visibility)
 * @method array|false getMetadata($path)
 * @method Handler get($path, Handler $handler = null)
 * @method Filesystem flushCache()
 * @method assertPresent($path)
 * @method assertAbsent($path)
 * @method Filesystem addPlugin(PluginInterface $plugin)
 */
class MountManager
{
    use PluggableTrait;

    /**
     * @var array
     */
    protected $filesystems = [];

    /**
     * @var bool
     */
    protected $automount = false;

    /**
     * Constructor.
     *
     * @param array $filesystems [string $filesystemPrefix => FilesystemInterface]
     */
    public function __construct(array $filesystems = [])
    {
        $this->mountFilesystems($filesystems);
    }

    /**
     * Mount filesystems.
     *
     * @param array $filesystems [:prefix => Filesystem,]
     *
     * @return $this
     */
    public function mountFilesystems(array $filesystems)
    {
        foreach ($filesystems as $prefix => $filesystem) {
            $this->mountFilesystem($prefix, $filesystem);
        }

        return $this;
    }

    /**
     * Mount filesystems.
     *
     * @param string              $prefix
     * @param FilesystemInterface $filesystem
     *
     * @return $this
     */
    public function mountFilesystem($prefix, FilesystemInterface $filesystem)
    {
        if (! is_string($prefix)) {
            throw new InvalidArgumentException(__METHOD__.' expects argument #1 to be a string.');
        }

        $this->filesystems[$prefix] = $filesystem;

        return $this;
    }

    /**
     * @param bool $automount
     * @return $this
     */
    public function setAutomount($automount)
    {
        $this->automount = $automount;

        return $this;
    }

    /**
     * Get the filesystem with the corresponding prefix.
     *
     * @param string $prefix
     *
     * @throws LogicException
     *
     * @return FilesystemInterface
     */
    public function getFilesystem($prefix)
    {
        $filesystem = $this->findFilesystem($prefix);
        if (false === $filesystem) {
            throw new LogicException('No filesystem mounted with prefix '.$prefix);
        }

        return $filesystem;
    }

    /**
     * @param string $prefix
     * @return FilesystemInterface|bool File system or FALSE if not found for given prefix
     */
    public function findFilesystem($prefix)
    {
        if (isset($this->filesystems[$prefix])) {
            return $this->filesystems[$prefix];
        } elseif ($this->automount) {
            $prefix = $this->automountFilesystem($prefix);
            return $this->filesystems[$prefix];
        } else {
            return false;
        }
    }

    /**
     * Retrieve the prefix from an arguments array.
     *
     * @param array $arguments
     *
     * @return array [:prefix, :arguments]
     */
    public function filterPrefix(array $arguments)
    {
        if (empty($arguments)) {
            throw new LogicException('At least one argument needed');
        }

        $path = array_shift($arguments);

        if (is_array($path)) {
            return [$path['prefix'], [$path['path']]];
        }

        if (! is_string($path)) {
            throw new InvalidArgumentException('First argument should be a string');
        }

        if (! preg_match('#^[a-zA-Z0-9]+\:\/\/.*#', $path)) {
            throw new InvalidArgumentException('No prefix detected in for path: '.$path);
        }

        list($prefix, $path) = explode('://', $path, 2);
        array_unshift($arguments, $path);

        return [$prefix, $arguments];
    }

    /**
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {
        list($prefix, $arguments) = $this->filterPrefix([$directory]);
        $filesystem = $this->getFilesystem($prefix);
        $directory = array_shift($arguments);
        $result = $filesystem->listContents($directory, $recursive);

        foreach ($result as &$file) {
            $file['filesystem'] = $prefix;
        }

        return $result;
    }

    /**
     * Call forwarder.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if ($this->automount) {
            $prefix = $this->automountFilesystem($arguments[0]);
            $arguments = [(new Uri($arguments[0]))->getPath()];
        } else {
            list($prefix, $arguments) = $this->filterPrefix($arguments);
        }

        $filesystem = $this->getFilesystem($prefix);

        try {
            return $this->invokePlugin($method, $arguments, $filesystem);
        } catch (PluginNotFoundException $e) {
            // Let it pass, it's ok, don't panic.
        }

        $callback = [$filesystem, $method];

        return call_user_func_array($callback, $arguments);
    }

    /**
     * @param $from
     * @param $to
     *
     * @return bool
     */
    public function copy($from, $to)
    {
        if ($this->automount) {
            $prefixFrom = $this->automountFilesystem($from);
            $argumentsFrom = [(new Uri($from))->getPath()];
            $prefixTo = $this->automountFilesystem($to);
            $argumentsTo = [(new Uri($to))->getPath()];
        } else {
            list($prefixFrom, $argumentsFrom) = $this->filterPrefix([$from]);
            list($prefixTo, $argumentsTo) = $this->filterPrefix([$to]);
        }

        $fsFrom = $this->getFilesystem($prefixFrom);
        $buffer = call_user_func_array([$fsFrom, 'readStream'], $argumentsFrom);

        if ($buffer === false) {
            return false;
        }

        $fsTo = $this->getFilesystem($prefixTo);
        $result =  call_user_func_array([$fsTo, 'writeStream'], array_merge($argumentsTo, [$buffer]));

        if (is_resource($buffer)) {
            fclose($buffer);
        }

        return $result;
    }

    /**
     * Move a file.
     *
     * @param $from
     * @param $to
     */
    public function move($from, $to)
    {
        $copied = $this->copy($from, $to);

        if ($copied) {
            return $this->delete($from);
        }

        return false;
    }

    /**
     * @param Uri $uri
     * @return string
     */
    public function getFilesystemPrefix(Uri $uri)
    {
        switch ($uri->getScheme()) {
            case '':
            case 'file':
                return 'file';

            case 'ftp':
                return ($uri->withPath('')->withFragment('')->withQuery('')->__toString());

            default:
                throw new \InvalidArgumentException('Could not determine filesystem prefix for URI: ' . $uri);
        }
    }

    /**
     * @param Uri $uri
     * @return string
     */
    public function getFilesystemRoot(Uri $uri)
    {
        switch ($uri->getScheme()) {
            case '': // local file, without "file://"
            case 'file': // local file
            case 'ftp':
                return '/';

            default:
                throw new \InvalidArgumentException('Could not determine filesystem root for URI: ' . $uri);
        }
    }

    /**
     * @param Uri $uriString
     * @return string mounted filesystem prefix
     */
    private function automountFilesystem($uriString)
    {
        $uri = new Uri($uriString);
        $filesystemPrefix = $this->getFilesystemPrefix($uri);
        $filesystemRoot = $this->getFilesystemRoot($uri);

        $filesystem = isset($this->filesystems[$filesystemPrefix]) ? $this->filesystems[$filesystemPrefix] : false;
        if (false === $filesystem) {
            $adapter = null;

            switch ($uri->getScheme()) {
                case '':
                case 'file':
                    $adapter = new Local($filesystemRoot);
                    break;

                case 'ftp':
                    $adapter = new Ftp([
                        'host' => $uri->getHost(),
                        'username' => explode(':', $uri->getUserInfo())[0],
                        'password' => explode(':', $uri->getUserInfo())[1],
                        'port' => $uri->getPort(),
                        'root' => $filesystemRoot
                    ]);
                    break;

                default:
                    throw new \InvalidArgumentException('Adapter not found for given URI Scheme: ' . $uri->getScheme());
            }

            $filesystem = new Filesystem($adapter);
            $this->mountFilesystem($filesystemPrefix, $filesystem);
        }

        return $filesystemPrefix;
    }
}
