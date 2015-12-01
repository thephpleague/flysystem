<?php

namespace League\Flysystem;

require_once('FileTests.php');

class AtomicFileTests extends FileTests
{
    public function setup()
    {
        clearstatcache();
        $fs = new Adapter\AtomicLocal(__DIR__.'/');
        $fs->deleteDir('files');
        $fs->createDir('files', new Config());
        $fs->write('file.txt', 'contents', new Config());
        $this->filesystem = new Filesystem($fs);
    }
}
