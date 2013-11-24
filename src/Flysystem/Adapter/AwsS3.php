<?php

namespace Flysystem\Adapter;

use Aws\S3\S3Client;
use Aws\S3\Enum\Group;
use Aws\S3\Enum\Permission;
use Flysystem\AdapterInterface;
use Flysystem\Util;

class AwsS3 extends AbstractAdapter
{
    protected static $resultMap = array(
        'Body'          => 'contents',
        'ContentLength' => 'size',
        'ContentType'   => 'mimetype',
        'Size'          => 'size',
    );

    protected $bucket;
    protected $client;
    protected $prefix;
    protected $options;

    public function __construct(S3Client $client, $bucket, $prefix = null, array $options = array())
    {
        $this->bucket = $bucket;
        $this->prefix = $prefix;
        $this->options = $options;
        $this->client = $client;
    }

    public function has($path)
    {
        return $this->client->doesObjectExist($this->bucket, $this->prefix($path));
    }

    public function write($path, $contents, $visibility = null)
    {
        $options = $this->getOptions($path, array(
            'Body' => $contents,
            'ContentType' => Util::contentMimetype($contents),
            'ContentLength' => Util::contentSize($contents),
        ));

        if ($visibility) {
            $options['ACL'] = $visibility === AdapterInterface::VISIBILITY_PUBLIC ? 'public-read' : 'private';
        }

        $this->client->putObject($options);
        $options['visibility'] = $visibility;

        return $this->normalizeObject($options);
    }

    public function writeStream($path, $resource, $visibility = null)
    {
        $options = $this->getOptions($path, array(
            'Body' => $resource,
        ));

        if ($visibility) {
            $options['ACL'] = $visibility === AdapterInterface::VISIBILITY_PUBLIC ? 'public-read' : 'private';
        }

        $this->client->putObject($options);
        $options['visibility'] = $visibility;

        return $this->normalizeObject($options);
    }

    public function update($path, $contents)
    {
        return $this->write($path, $contents);
    }

    public function updateStream($path, $resource)
    {
        return $this->write($path, $resource);
    }

    public function read($path)
    {
        $options = $this->getOptions($path);
        $result = $this->client->getObject($options);

        return $this->normalizeObject($result->getAll());
    }

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

    public function delete($path)
    {
        $options = $this->getOptions($path);

        return $this->client->deleteObject($options);
    }

    public function deleteDir($path)
    {
        return $this->client->deleteMatchingObjects($this->bucket, $this->prefix($path));
    }

    public function createDir($path)
    {
        return array('path' => $path, 'type' => 'dir');
    }

    public function getMetadata($path)
    {
        $options = $this->getOptions($path);
        $result = $this->client->headObject($options);

        return $this->normalizeObject($result->getAll(), $path);
    }

    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    public function getVisibility($path)
    {
        $options = $this->getOptions($path);
        $result = $this->client->getObjectAcl($options)->getAll();

        foreach ($result['Grants'] as $grant) {
            if (isset($grant['Grantee']['URI']) and $grant['Grantee']['URI'] === Group::ALL_USERS and $grant['Permission'] === Permission::READ) {
                return array('visibility' => AdapterInterface::VISIBILITY_PUBLIC);
            }
        }

        return array('visibility' => AdapterInterface::VISIBILITY_PRIVATE);
    }

    public function setVisibility($path, $visibility)
    {
        $options = $this->getOptions($path, array(
            'ACL' => $visibility === AdapterInterface::VISIBILITY_PUBLIC ? 'public-read' : 'private',
        ));

        $this->client->putObjectAcl($options);

        return compact('visibility');
    }

    public function listContents($dirname = '', $recursive = false)
    {
        $result = $this->client->listObjects(array(
            'Bucket' => $this->bucket,
        ))->getAll(array('Contents'));

        $result = array_map(array($this, 'normalizeObject'), $result['Contents']);

        return Util::emulateDirectories($result);
    }

    protected function normalizeObject($object, $path = null)
    {
        $result = array('path' => $path ?: $object['Key']);

        if (isset($object['LastModified'])) {
            $result['timestamp'] = strtotime($object['LastModified']);
        }

        $result = array_merge($result, Util::map($object, static::$resultMap), array('type' => 'file'));

        return $result;
    }

    protected function getOptions($path, array $options = array())
    {
        $options['Key'] = $this->prefix($path);
        $options['Bucket'] = $this->bucket;

        return array_merge($this->options, $options);
    }

    protected function prefix($path)
    {
        if (! $this->prefix) {
            return $path;
        }

        return $this->prefix.'/'.$path;
    }
}
