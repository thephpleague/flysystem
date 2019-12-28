<?php

declare(strict_types=1);

namespace League\Flysystem\InMemory;

use finfo;

use const FILEINFO_MIME_TYPE;

class InMemoryFile
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $contents;

    /**
     * @var int
     */
    private $lastModified;

    /**
     * @var mixed
     */
    private $visibility;

    public function __construct()
    {
        $this->lastModified = time();
    }

    public function updateContents(string $contents): void
    {
        $this->contents = $contents;
        $this->lastModified = time();
    }

    public function lastModified(): int
    {
        return $this->lastModified;
    }

    public function read(): string
    {
        return $this->contents;
    }

    public function readStream()
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $this->contents);
        rewind($stream);

        return $stream;
    }

    public function fileSize(): int
    {
        return function_exists('mb_strlen')
            ? mb_strlen($this->contents)
            : strlen($this->contents);
    }

    public function mimeType(): string
    {
        return (new finfo(FILEINFO_MIME_TYPE))->buffer($this->contents);
    }

    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
    }

    public function visibility()
    {
        return $this->visibility;
    }
}
