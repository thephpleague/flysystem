<?php

namespace Flysystem;

interface ReadInterface
{
    public function has($path);
    public function read($path);
    public function listContents($directory = '', $recursive = false);
    public function getMetadata($path);
    public function getSize($path);
    public function getMimetype($path);
    public function getTimestamp($path);
    public function getVisibility($path);
}
