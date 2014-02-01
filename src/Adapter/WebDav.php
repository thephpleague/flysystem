<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Util;
use Sabre\DAV\Client;
use Sabre\DAV\Exception;

class WebDav extends AbstractAdapter
{
    protected static $resultMap = array(
        '{DAV:}getcontentlength' => 'size',
        '{DAV:}getcontenttype' => 'mimetype',
        'content-length' => 'size',
        'content-type' => 'mimetype',
    );

    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getMetadata($path)
    {
        try {
            $result = $this->client->propFind($path, array(
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

    public function has($path)
    {
        return $this->getMetadata($path);
    }

    public function read($path)
    {
        try {
            $response = $this->client->request('GET', $path);

            if ($response['statusCode'] !== 200) {
                return false;
            }

            return array_merge(array(
                'contents' => $response['body'],
                'timestamp' => strtotime($response['headers']['last-modified']),
            ), Util::map($response['headers'], static::$resultMap));
        } catch (Exception\FileNotFound $e) {
            return false;
        }
    }

    public function write($path, $contents, $config = null)
    {
        $config = Util::ensureConfig($config);
        $this->client->request('PUT', $path, $contents);

        $result = compact('path', 'contents');

        if ($config && $visibility = $config->get('visibility')) {
            $this->setVisibility($visibility);
        }

        return $result;
    }

    public function update($path, $contents)
    {
        return $this->write($path, $contents);
    }

    public function rename($path, $newpath)
    {
        try {
            $response = $this->client->request('MOVE', '/'.ltrim($path, '/'), null, array(
                'Destination' => '/'.ltrim($newpath, '/'),
            ));

            if ($response['statusCode'] > 200 || $response['statusCode'] < 299) {
                return true;
            }
        } catch (Exception\FileNotFound $e) { }

        return false;
    }

    public function delete($path)
    {
        try {
            $this->client->request('DELETE', $path);

            return true;
        } catch (Exception\FileNotFound $e) {
            return false;
        }
    }

    public function createDir($path)
    {
        $response = $this->client->request('MKCOL', $path);

        return $response['statusCode'] === 201;
    }

    public function deleteDir($dirname)
    {
        return $this->delete($dirname);
    }

    public function listContents($directory = '', $recursive = false)
    {
        $response = $this->client->propFind($directory, array(
            '{DAV:}displayname',
            '{DAV:}getcontentlength',
            '{DAV:}getcontenttype',
            '{DAV:}getlastmodified',
        ), 1);

        array_shift($response);

        $result = array();

        foreach ($response as $path => $object) {
            $object = $this->normalizeObject($object, $path);
            $result[] = $object;

            if ($recursive && $object['type'] === 'dir') {
                $result = array_merge($result, $this->listContents($object['path'], true));
            }
        }

        return $result;
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
        return $this->getMetadata($path);
    }

    protected function normalizeObject($object, $path)
    {
        if ( ! isset($object['{DAV:}getcontentlength'])) {
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
