<?php

declare(strict_types=1);

namespace League\Flysystem\InMemory;

use Generator;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;

class InMemoryFilesystem implements FilesystemAdapter
{
    /**
     * @var InMemoryFile[]
     */
    private $files = [];

    public function fileExists(string $path): bool
    {
        return array_key_exists($path, $this->files);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $file = $this->files[$path] = $this->files[$path] ?? new InMemoryFile($path);
        $file->updateContents($contents);

        if ($visibility = $config->get('visibility')) {
            $file->setVisibility($visibility);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, stream_get_contents($contents), $config);
    }

    public function update(string $path, string $contents, Config $config): void
    {
        $this->write($path, $contents, $config);
    }

    public function updateStream(string $path, $contents, Config $config): void
    {
        $this->write($path, stream_get_contents($contents), $config);
    }

    public function read(string $path): string
    {
        if (array_key_exists($path, $this->files) === false) {
            throw UnableToReadFile::atLocation($path, 'file does not exist');
        }

        return $this->files[$path]->read();
    }

    public function readStream(string $path)
    {
        if (array_key_exists($path, $this->files) === false) {
            throw UnableToReadFile::atLocation($path, 'file does not exist');
        }

        return $this->files[$path]->readStream();
    }

    public function delete(string $path): void
    {
        unset($this->files[$path]);
    }

    public function deleteDirectory(string $prefix): void
    {
        $prefix = trim($prefix, '/') . '/';

        foreach (array_keys($this->files) as $path) {
            if (strpos($path, $prefix) === 0) {
                unset($this->files[$path]);
            }
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        // ignored
    }

    public function setVisibility(string $path, string $visibility): void
    {
        if (array_key_exists($path, $this->files) === false) {
            throw UnableToSetVisibility::atLocation($path, 'file does not exist');
        }

        $this->files[$path]->setVisibility($visibility);
    }

    public function visibility(string $path): string
    {
        if (array_key_exists($path, $this->files) === false) {
            throw UnableToRetrieveMetadata::visibility($path, 'file does not exist');
        }

        return $this->files[$path]->visibility();
    }

    public function mimeType(string $path): string
    {
        if (array_key_exists($path, $this->files) === false) {
            throw UnableToRetrieveMetadata::mimeType($path, 'file does not exist');
        }

        return $this->files[$path]->mimeType();
    }

    public function lastModified(string $path): int
    {
        if (array_key_exists($path, $this->files) === false) {
            throw UnableToRetrieveMetadata::lastModified($path, 'file does not exist');
        }

        return $this->files[$path]->lastModified();
    }

    public function fileSize(string $path): int
    {
        if (array_key_exists($path, $this->files) === false) {
            throw UnableToRetrieveMetadata::fileSize($path, 'file does not exist');
        }

        return $this->files[$path]->fileSize();
    }

    public function listContents(string $prefix, bool $recursive): Generator
    {
        $prefix = rtrim($prefix, '/') . '/';
        $prefixLength = strlen($prefix);
        $listedDirectories = [];

        foreach (array_keys($this->files) as $path) {
            if (substr($path, 0, $prefixLength) === $prefix) {
                $subPath = substr($path, $prefixLength);
                $dirname = dirname($subPath);

                if ($dirname !== '.') {
                    $parts = explode('/', $dirname);
                    $dirPath = '';

                    foreach ($parts as $part) {
                        $dirPath .= $part.'/';

                        if ( ! in_array($dirPath, $listedDirectories)) {
                            yield new DirectoryAttributes($dirPath);
                        }
                    }
                }

                if ($recursive) {
                    yield new FileAttributes($path);
                }
            }
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
    }

    public function copy(string $source, string $destination, Config $config): void
    {
    }
}
