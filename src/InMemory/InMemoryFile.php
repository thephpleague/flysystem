<?php

declare(strict_types=1);

namespace League\Flysystem\InMemory;

use const FILEINFO_MIME_TYPE;
use finfo;

/**
 * @internal
 */
class InMemoryFile
{
    private string $contents = '';
    private int $lastModified = 0;
    private ?string $visibility = null;

    public function updateContents(string $contents, ?int $timestamp): void
    {
        $this->contents = $contents;
        $this->lastModified = $timestamp ?? time();
    }

    public function lastModified(): int
    {
        return $this->lastModified;
    }

    public function withLastModified(int $lastModified): self
    {
        $clone = clone $this;
        $clone->lastModified = $lastModified;

        return $clone;
    }

    public function read(): string
    {
        return $this->contents;
    }

    /**
     * @return resource
     */
    public function readStream()
    {
        /** @var resource $stream */
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $this->contents);
        rewind($stream);

        return $stream;
    }

    public function fileSize(): int
    {
        return strlen($this->contents);
    }

    public function mimeType(): string
    {
        return (string) (new finfo(FILEINFO_MIME_TYPE))->buffer($this->contents);
    }

    public function setVisibility(string $visibility): void
    {
        $this->visibility = $visibility;
    }

    public function visibility(): ?string
    {
        return $this->visibility;
    }
}
