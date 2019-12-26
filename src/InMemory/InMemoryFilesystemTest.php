<?php

declare(strict_types=1);

namespace League\Flysystem\InMemory;

use League\Flysystem\Config;
use PHPUnit\Framework\TestCase;

class InMemoryFilesystemTest extends TestCase
{
    const PATH = 'path.txt';

    /**
     * @var InMemoryFilesystem
     */
    private $adapter;

    protected function setUp(): void
    {
        $this->adapter = new InMemoryFilesystem();
    }

    /**
     * @test
     */
    public function writing_and_reading_a_file()
    {
        $this->adapter->write(self::PATH, 'contents', new Config());
        $contents = $this->adapter->read(self::PATH);
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function checking_for_metadata()
    {
        mock_function('time', 1234, 1234);
        $this->adapter->write(
            self::PATH,
            file_get_contents(__DIR__.'/../../test_files/flysystem.svg'),
            new Config()
        );

        $this->assertTrue($this->adapter->fileExists(self::PATH));
        $this->assertEquals(753, $this->adapter->fileSize(self::PATH));
        $this->assertEquals(1234, $this->adapter->lastModified(self::PATH));
        $this->assertEquals('image/svg', $this->adapter->mimeType(self::PATH));
    }
}
