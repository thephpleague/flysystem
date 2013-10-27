<?php

namespace Flysystem;

interface AdapterInterface extends ReadInterface
{
	const VISIBILITY_PUBLIC = 'public';
	const VISIBILITY_PRIVATE = 'private';
	public function write($path, $contents, $visibility);
	public function update($path, $contents);
	public function rename($path, $newpath);
	public function delete($path);
	public function deleteDir($dirname);
	public function createDir($dirname);
	public function setVisibility($path, $visibility);
}