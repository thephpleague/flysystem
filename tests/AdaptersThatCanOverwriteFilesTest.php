<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Filesystem;
use League\Flysystem\Stub\FileOverwritingAdapterStub;
use PHPUnit\Framework\TestCase;

class AdaptersThatCanOverwriteFilesTest extends TestCase
{

    /**
     * @test
     */
    public function overwriting_files_with_put()
    {
        $filesystem = new Filesystem($adapter = new FileOverwritingAdapterStub());
        $filesystem->put('path.txt', 'string contents');

        $this->assertEquals('path.txt', $adapter->writtenPath);
        $this->assertEquals('string contents', $adapter->writtenContents);
    }

    /**
     * @test
     */
    public function overwriting_files_with_putStream()
    {
        $filesystem = new Filesystem($adapter = new FileOverwritingAdapterStub());
        $stream = tmpfile();
        fwrite($stream, 'stream contents');
        $filesystem->putStream('path.txt', $stream);
        fclose($stream);

        $this->assertEquals('path.txt', $adapter->writtenPath);
        $this->assertEquals('stream contents', $adapter->writtenContents);
    }
}
