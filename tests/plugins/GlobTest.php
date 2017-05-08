<?php

/**
 * Class GlobTest
 */
class GlobTest extends PHPUnit_Framework_TestCase
{
    protected $validSamples = array(
        array(
            'dirname' => '',
            'basename' => 'some-file.txt',
            'extension' => 'txt',
            'filename' => 'some-file',
            'path' => 'some-file.txt',
        ),
        array(
            'dirname' => 'some-folder',
            'basename' => 'some-file.txt',
            'extension' => 'txt',
            'filename' => 'some-file',
            'path' => 'some-folder/some-file.txt',
        ),
    );

    protected function getFsWithGlobPlugin()
    {
        $glob = new \League\Flysystem\Plugin\Glob();

        $adapter = \Mockery::mock('\League\Flysystem\AdapterInterface')->makePartial();
        //$adapter = new \League\Flysystem\Adapter\Local('/home/marc/Desktop');
        /** @var \League\Flysystem\Filesystem $fs */
        $fs = new \League\Flysystem\Filesystem($adapter);
        $fs->addPlugin($glob);

        $adapter->shouldReceive('listContents')
            ->once()
            ->with('', true)
            ->andReturn($this->validSamples);

        return $fs;
    }

    public function testArrayGlob()
    {
        $fs = $this->getFsWithGlobPlugin();

        $result = $fs->glob('*.txt');

        $this->assertTrue(is_array($result));

        foreach ($result as $file) {
            $this->assertContains($file, $this->validSamples);
        }
    }

    /**
     * @requires PHP 5.5
     */
    public function testYieldOnGlob()
    {
        $fs = $this->getFsWithGlobPlugin();

        $result = $fs->glob('*.txt', true);
        $this->assertTrue($result instanceof Generator);

        foreach ($result as $file) {
            $this->assertContains($file, $this->validSamples);
        }
    }
}
