<?php

namespace Flysystem;

interface CacheInterface extends ReadInterface
{
    public function isComplete($dirname, $recursive);
    public function setComplete($dirname, $recursive);
    public function storeContents($directory, array $contents, $recursive);
    public function flush();
    public function autosave();
    public function save();
    public function load();
}
