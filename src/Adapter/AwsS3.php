<?php

namespace League\Flysystem\Adapter;

use Aws\S3\S3Client;
use Aws\S3\Enum\Group;
use Aws\S3\Enum\Permission;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;

class AwsS3 extends AbstractAdapter
{
    /**
     * @var  array  $resultMap
     */
    protected static $resultMap = array(
        'Body'          => 'contents',
        'ContentLength' => 'size',
        'ContentType'   => 'mimetype',
        'Size'          => 'size',
    );

    /**
     * @var  array  $metaOptions
     */
    protected static $metaOptions = array(
        'Cache-Control',
        'Expires',
    );

    /**
     * @var  string  $bucket  bucket name
     */
    protected $bucket;

    /**
     * @var  Aws\S3\S3Client  $client  S3 Client
     */
    protected $client;

    /**
     * @var  string  $prefix  path prefix
     */
    protected $prefix;

    /**
     * @var  array  $options  default options
     */
    protected $options = array();

    /**
     * Constructor
     *
     * @param  S3Client  $client
     * @param  string    $bucket
     * @param  string    $prefix
     * @param  array     $options
     */
    public function __construct(S3Client $client, $bucket, $prefix = null, array $options = array())
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->prefix = $prefix;
        $this->options = $options;
    }

    /**
     * Check whether a file exists
     *
     * @param   string  $path
     * @return  bool    weather an object result
     */
    public function has($path)
    {
        return $this->client->doesObjectExist($this->bucket, $this->prefix($path));
    }

    /**
     * Write a file
     *
     * @param   string  $path
     * @param   string  $contents
     * @param   mixed   $config
     * @return  array   file metadata
     */
    public function write($path, $contents, $config = null)
    {
        $config = Util::ensureConfig($config);
        $options = $this->getOptions($path, array(
            'Body' => $contents,
            'ContentType' => Util::guessMimeType($path, $contents),
            'ContentLength' => Util::contentSize($contents),
        ), $config);

        $result = $this->client->putObject($options);

        if ($result === false) {
            return false;
        }

        return $this->normalizeObject($options);
    }

    /**
     * Write using a stream
     *
     * @param   string    $path
     * @param   resource  $resource
     * @param   mixed     $config
     *
     * @return  array     file metadata
     */
    public function writeStream($path, $resource, $config = null)
    {
        $config = Util::ensureConfig($config);
        $options = $this->getOptions($path, array(
            'Body' => $resource,
        ), $config);

        $this->client->putObject($options);

        return $this->normalizeObject($options);
    }

    /**
     * Update a file
     *
     * @param   string  $path
     * @param   string  $contents
     * @param   mixed   $config   Config object or visibility setting
     * @return  array   file metadata
     */
    public function update($path, $contents, $config = null)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * Update a file using a stream
     *
     * @param   string    $path
     * @param   resource  $resource
     * @param   mixed        $config   Config object or visibility setting
     * @return  array     file metadata
     */
    public function updateStream($path, $resource, $config = null)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * Read a file
     *
     * @param   string  $path
     * @return  array   file metadata
     */
    public function read($path)
    {
        $options = $this->getOptions($path);
        $result = $this->client->getObject($options);

        return $this->normalizeObject($result->getAll(), $path);
    }

    /**
     * Get a read-stream for a file
     *
     * @param   string  $path
     * @return  array   file metadata
     */
    public function readStream($path)
    {
        if ( ! in_array('s3', stream_get_wrappers())) {
            $this->client->registerStreamWrapper();
        }

        $context = stream_context_create(array(
            's3' => array('seekable' => true),
        ));

        $stream = fopen('s3://'.$this->bucket.'/'.$this->prefix($path), 'r', false, $context);

        return compact('stream');
    }

    /**
     * Rename a file
     *
     * @param   string  $path
     * @param   string  $newpath
     * @return  array   file metadata
     */
    public function rename($path, $newpath)
    {
        $options = $this->getOptions($newpath, array(
            'Bucket' => $this->bucket,
            'CopySource' => $this->bucket.'/'.$this->prefix($path),
        ));

        $result = $this->client->copyObject($options)->getAll();
        $result = $this->normalizeObject($result, $newpath);
        $this->delete($path);

        return $result;
    }

    /**
     * Copy a file
     *
     * @param   string  $path
     * @param   string  $newpath
     * @return  array   file metadata
     */
    public function copy($path, $newpath)
    {
        $options = $this->getOptions($newpath, array(
            'Bucket' => $this->bucket,
            'CopySource' => $this->bucket.'/'.$this->prefix($path),
        ));

        $result = $this->client->copyObject($options)->getAll();

        return $this->normalizeObject($result, $newpath);
    }

    /**
     * Delete a file
     *
     * @param   string   $path
     * @return  boolean  delete result
     */
    public function delete($path)
    {
        $options = $this->getOptions($path);

        return $this->client->deleteObject($options);
    }

    /**
     * Delete a directory (recursive)
     *
     * @param   string   $path
     * @return  boolean  delete result
     */
    public function deleteDir($path)
    {
        $prefix = rtrim($this->prefix($path), '/') . '/';

        return $this->client->deleteMatchingObjects($this->bucket, $prefix);
    }

    /**
     * Create a directory
     *
     * @param   string  $path
     * @return  array   directory metadata
     */
    public function createDir($path)
    {
        $result = $this->write(rtrim($path, '/') . '/', '');

        if ( ! $result) {
            return false;
        }

        return array('path' => $path, 'type' => 'dir');
    }

    /**
     * Get metadata for a file
     *
     * @param   string  $path
     * @return  array   file metadata
     */
    public function getMetadata($path)
    {
        $options = $this->getOptions($path);
        $result = $this->client->headObject($options);

        return $this->normalizeObject($result->getAll(), $path);
    }

    /**
     * Get the mimetype of a file
     *
     * @param   string  $path
     * @return  array   file metadata
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the file of a file
     *
     * @param   string  $path
     * @return  array   file metadata
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the timestamp of a file
     *
     * @param   string  $path
     * @return  array   file metadata
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the visibility of a file
     *
     * @param   string  $path
     * @return  array   file metadata
     */
    public function getVisibility($path)
    {
        $options = $this->getOptions($path);
        $result = $this->client->getObjectAcl($options)->getAll();
        $visibility = AdapterInterface::VISIBILITY_PRIVATE;

        foreach ($result['Grants'] as $grant) {
            if (isset($grant['Grantee']['URI']) && $grant['Grantee']['URI'] === Group::ALL_USERS && $grant['Permission'] === Permission::READ) {
                $visibility = AdapterInterface::VISIBILITY_PUBLIC;
                break;
            }
        }

        return compact('visibility');
    }

    /**
     * Get mimetype of a file
     *
     * @param   string  $path
     * @param   string  $visibility
     * @return  array   file metadata
     */
    public function setVisibility($path, $visibility)
    {
        $options = $this->getOptions($path, array(
            'ACL' => $visibility === AdapterInterface::VISIBILITY_PUBLIC ? 'public-read' : 'private',
        ));

        $this->client->putObjectAcl($options);

        return compact('visibility');
    }

    /**
     * List contents of a directory
     *
     * @param   string  $dirname
     * @param   bool    $recursive
     * @return  array   directory contents
     */
    public function listContents($dirname = '', $recursive = false)
    {
        $objectsIterator = $this->client->getIterator('listObjects', array(
            'Bucket' => $this->bucket,
            'Prefix' => $this->prefix($dirname),
        ));

        $contents = iterator_to_array($objectsIterator);
        $result = array_map(array($this, 'normalizeObject'), $contents);

        return Util::emulateDirectories($result);
    }

    /**
     * Normalize a result from AWS
     *
     * @param   string  $object
     * @param   string  $path
     * @return  array   file metadata
     */
    protected function normalizeObject($object, $path = null)
    {
        $result = array('path' => $path ?: $object['Key']);

        if (isset($object['LastModified'])) {
            $result['timestamp'] = strtotime($object['LastModified']);
        }

        if (substr($result['path'], -1) === '/') {
            $result['type'] = 'dir';
            $result['path'] = rtrim($result['path'], '/');
            $result['dirname'] = Util::dirname($result['path']);

            return $result;
        }

        $result = array_merge($result, Util::map($object, static::$resultMap), array('type' => 'file'));
        $result['dirname'] = Util::dirname($result['path']);

        if (isset($result['contents'])) {
            $result['contents'] = (string) $result['contents'];
        }

        return $result;
    }

    /**
     * Get options for a AWS call
     *
     * @param   string  $path
     * @param   array   $options
     *
     * @return  array   AWS options
     */
    protected function getOptions($path, array $options = array(), Config $config = null)
    {
        $options['Key'] = $this->prefix($path);
        $options['Bucket'] = $this->bucket;

        if ($config) {
            $options = array_merge($options, $this->getOptionsFromConfig($config));
        }

        return array_merge($this->options, $options);
    }

    /**
     * Retrieve options from a Config instance
     *
     * @param   Config  $config
     * @return  array
     */
    protected function getOptionsFromConfig(Config $config)
    {
        $options = array();

        foreach (static::$metaOptions as $option) {
            if ( ! $config->has($option)) continue;
            $options[$option] = $config->get($option);
        }

        if ($visibility = $config->get('visibility')) {
            // For local reference
            $options['visibility'] = $visibility;
            // For external reference
            $options['ACL'] = $visibility === AdapterInterface::VISIBILITY_PUBLIC ? 'public-read' : 'private';
        }

        if ($mimetype = $config->get('mimetype')) {
            // For local reference
            $options['mimetype'] = $mimetype;
            // For external reference
            $options['ContentType'] = $mimetype;
        }

        return $options;
    }

    /**
     * Prefix a path
     *
     * @param   string  $path
     * @return  string  prefixed path
     */
    protected function prefix($path)
    {
        if (! $this->prefix) {
            return $path;
        }

        return $this->prefix.'/'.$path;
    }
}
