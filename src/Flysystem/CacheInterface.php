<?php

namespace Flysystem;

interface CacheInterface extends ReadInterface
{
	public function isComplete();
	public function setComplete($complete = true);
	public function storeContents(array $contents);
	public function flush();
	public function autosave();
	public function save();
	public function load();
}