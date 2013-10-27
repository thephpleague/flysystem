<?php

namespace Flysystem\Adapter;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Flysystem\Util;

class AwsS3 extends AbstractAdapter
{
	protected static $resultMap = [
		'Body'          => 'contents',
		'ContentLength' => 'size',
		'ContentType'   => 'mimetype',
		'Size'          => 'size',
	];

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

	public function write($path, $contents)
	{
		$options = $this->getOptions($path, [
			'Body' => $contents,
			'ContentType' => Util::contentMimetype($contents),
			'ContentLength' => Util::contentSize($contents),
		]);

		$this->client->putObject($options);

		return $this->normalizeObject($options);
	}

	public function update($path, $contents)
	{
		return $this->write($path, $contents);
	}

	public function read($path)
	{
		$options = $this->getOptions($path);
		$result = $this->client->getObject($options);

		return $this->normalizeObject($result->getAll());
	}

	public function rename($path, $newpath)
	{
		$options = $this->getOptions($newpath, [
			'Bucket' => $this->bucket,
			'CopySource' => $this->bucket.'/'.$this->prefix($path),
		]);

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
		$this->client->deleteMatchingObjects($this->bucket, $this->prefix($path));
	}

	public function createDir($path)
	{
		return compact('path');
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

	public function listContents()
	{
		$result = $this->client->listObjects([
			'Bucket' => $this->bucket,
		])->getAll(['Contents']);

		if ( ! isset($result['Contents'])) {
			return [];
		}

		$result = array_map([$this, 'normalizeObject'], $result['Contents']);

		return $this->emulateDirectories($result);
	}

	protected function normalizeObject($object, $path = null)
	{
		$result = ['path' => $path ?: $object['Key']];

		if (isset($object['LastModified'])) {
			$object['timestamp'] = strtotime($object['LastModified']);
		}

		$result = array_merge($result, Util::map($object, static::$resultMap), ['type' => 'file']);

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
		if ( ! $this->prefix) {
			return $path;
		}

		return $this->prefix.'/'.$path;
	}
}