<?php

namespace Flysystem;

interface AdapterInterface extends ReadInterface
{
	public function write($path, $contents);
	public function update($path, $contents);
	public function rename($path, $newpath);
	public function delete($path);
	public function deleteDir($dirname);
	public function createDir($dirname);
}