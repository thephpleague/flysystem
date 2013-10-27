<?php

namespace Flysystem\Adapter;

use Finfo;
use SplFileInfo;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Flysystem\Util;

class Local extends AbstractAdapter
{
	const TYPE_INFO= 'info';
	const TYPE_PATH = 'path';

	public function __construct($root)
	{
		$root = realpath($root);

		$this->root = Util::normalizePrefix($root, DIRECTORY_SEPARATOR);
	}

	protected function prefix($path)
	{
		return $this->root.Util::normalizePath($path, DIRECTORY_SEPARATOR);
	}

	public function has($path)
	{
		return file_exists($this->prefix($path));
	}

	public function write($path, $contents)
	{
		$location = $this->prefix($path);

		if ($dirname = dirname($path) !== '.') {
			$this->ensureDirectory($dirname);
		}

		$size = file_put_contents($location, $contents, LOCK_EX);

		return array_merge($pathinfo, [
			'contents' => $contents,
			'type' => 'file',
		]);
	}

	public function update($path, $contents)
	{
		$location = $this->prefix($path);

		return file_put_contents($location, $contents, LOCK_EX);
	}

	public function read($path)
	{
		return ['contents' => file_get_contents($this->prefix($path))];
	}

	public function rename($path, $newpath)
	{
		$location = $this->prefix($path);
		$destination = $this->prefix($newpath);

		return rename($location, $destination);
	}

	public function delete($path)
	{
		return unlink($this->prefix($path));
	}

	public function listContents()
	{
		$paths = $this->directoryContents('', false);

		return array_map([$this, 'getMetadata'], $paths);
	}

	public function getMetadata($path)
	{
		$location = $this->prefix($path);
		$info = new SplFileInfo($location);

		$meta['type'] = $info->getType();
		$meta['path'] = $path;

		if ($meta['type'] === 'file') {
			$meta['timestamp'] = $info->getMTime();
			$meta['size'] = $info->getSize();
		}

		return $meta;
	}

	public function getSize($path)
	{
		return $this->getMetadata($path);
	}

	public function getMimetype($path)
	{
		$location = $this->prefix($path);
		$finfo = new Finfo(FILEINFO_MIME_TYPE);

		return $finfo->file($location);
	}

	public function getTimestamp($path)
	{

	}

	public function createDir($dirname)
	{
		$location = $this->prefix($dirname);

		if ( ! is_dir($location)) {
			mkdir($location, 0777, true);
		}

		return ['path' => $dirname, 'type' => 'dir'];
	}

	public function deleteDir($dirname)
	{
		$contents = $this->directoryContents($dirname, static::TYPE_PATH);
		$contents = array_reverse($contents);

		foreach ($contents as $file) {
			$this->delete($file);
		}

		return unlink($this->prefix($dirname));
	}

	public function hasDir($dirname)
	{
		return is_dir($this->prefix($dirname));
	}

	protected function directoryContents($path = '', $info = self::TYPE_INFO)
	{
		$result = [];
		$path = $this->prefix($path).DIRECTORY_SEPARATOR;
		$length = strlen($path);
		$iterator = $this->getDirectoryIterator($path);

		foreach ($iterator as $file) {
			$path = substr($file->getPathname(), $length);
			$result[] = $info ? $this->normalizeFileInfo($path, $file) : $path;
		}

		return $result;
	}

	protected function normalizeFileInfo($path, $file)
	{
		$normalized = ['type' => $file->getType(), 'path' => $path];

		if ($normalized['type'] === 'file') {
			$normalized['timestamp'] = $file->getMTime();
			$normalized['size'] = $file->getSize();
		}

		return $normalized;
	}

	protected function getDirectoryIterator($path)
	{
		$directory = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
		$iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);

		return $iterator;
	}
}