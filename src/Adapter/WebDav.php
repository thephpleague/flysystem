<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Adapter\Polyfill\StreamedCopyTrait;
use League\Flysystem\Adapter\Polyfill\StreamedTrait;
use League\Flysystem\Config;
use League\Flysystem\Util;
use LogicException;
use Sabre\DAV\Client;
use Sabre\DAV\Exception;

class WebDav extends AbstractAdapter
{
    use StreamedTrait;
    use StreamedCopyTrait;
    use NotSupportingVisibilityTrait;

    /**
     * @var array
     */
    protected static $resultMap = array(
        '{DAV:}getcontentlength' => 'size',
        '{DAV:}getcontenttype' => 'mimetype',
        'content-length' => 'size',
        'content-type' => 'mimetype',
    );

    /**
     * @var Client
     */
    protected $client;

    /**
     * Constructor
     *
     * @param Client $client
     * @param string   $prefix
     */
    public function __construct(Client $client, $prefix = null)
    {
        $this->client = $client;
        $this->setPathPrefix($prefix);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        $location = $this->applyPathPrefix($path);

        try {
            $result = $this->client->propFind($location, array(
                '{DAV:}displayname',
                '{DAV:}getcontentlength',
                '{DAV:}getcontenttype',
                '{DAV:}getlastmodified',
            ));

            return $this->normalizeObject($result, $path);
        } catch (Exception\FileNotFound $e) {
            return false;
        }
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
    public function read($path)
    {
        $location = $this->applyPathPrefix($path);

        try {
            $response = $this->client->request('GET', $location);

            if ($response['statusCode'] !== 200) {
                return false;
            }

            return array_merge(array(
                'contents' => $response['body'],
                'timestamp' => strtotime($response['headers']['last-modified']),
                'path' => $path,
            ), Util::map($response['headers'], static::$resultMap));
        } catch (Exception\FileNotFound $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        $this->client->request('PUT', $location, $contents);

        $result = compact('path', 'contents');

        if ($config->get('visibility')) {
            throw new LogicException(__CLASS__ . ' does not support visibility settings.');
        }

        return $result;
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
    public function rename($path, $newpath)
    {
        $location = $this->applyPathPrefix($path);

        try {
            $response = $this->client->request('MOVE', '/'.ltrim($location, '/'), null, array(
                'Destination' => '/'.ltrim($newpath, '/'),
            ));

            if ($response['statusCode'] >= 200 && $response['statusCode'] < 300) {
                return true;
            }
        } catch (Exception\FileNotFound $e) {
            // Would have returned false here, but would be redundant
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        $location = $this->applyPathPrefix($path);

        try {
            $this->client->request('DELETE', $location);

            return true;
        } catch (Exception\FileNotFound $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($path, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        $response = $this->client->request('MKCOL', $location);

        if ($response['statusCode'] !== 201) {
            return false;
        }

        return compact('path') + ['type' => 'dir'];
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        return $this->delete($dirname);
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        $location = $this->applyPathPrefix($directory);

        $response = $this->client->propFind($location, array(
            '{DAV:}displayname',
            '{DAV:}getcontentlength',
            '{DAV:}getcontenttype',
            '{DAV:}getlastmodified',
        ), 1);

        array_shift($response);

        $result = array();

        foreach ($response as $path => $object) {
            $path = $this->removePathPrefix($path);
            $object = $this->normalizeObject($object, $path);
            $result[] = $object;

            if ($recursive && $object['type'] === 'dir') {
                $result = array_merge($result, $this->listContents($object['path'], true));
            }
        }

        return $result;
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
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Normalise a WebDAV repsonse object
     *
     * @param array  $object
     * @param string $path
     * @return array
     */
    protected function normalizeObject(array $object, $path)
    {
        if (! isset($object['{DAV:}getcontentlength'])) {
            return array('type' => 'dir', 'path' => trim($path, '/'));
        }

        $result = Util::map($object, static::$resultMap);

        if (isset($object['{DAV:}getlastmodified'])) {
            $result['timestamp'] = strtotime($object['{DAV:}getlastmodified']);
        }

        $result['type'] = 'file';
        $result['path'] = trim($path, '/');

        return $result;
    }
}
