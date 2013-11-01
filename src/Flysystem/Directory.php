<?php

namespace Flysystem;

class Directory extends Handler
{
	public function delete()
	{
		$this->filesystem->deleteDir($this->path);
	}
}
