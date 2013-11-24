<?php

namespace Flysystem;

interface AdapterInterface extends ReadInterface
{
    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_PRIVATE = 'private';
    public function write($path, $contents, $visibility = null);
    public function update($path, $contents);
    public function rename($path, $newpath);
    public function delete($path);
    public function deleteDir($dirname);
    public function createDir($dirname);
    public function setVisibility($path, $visibility);
    public function readStream($path);
    public function writeStream($path, $resource, $visibility = null);
    public function updateStream($path, $resource);
}
