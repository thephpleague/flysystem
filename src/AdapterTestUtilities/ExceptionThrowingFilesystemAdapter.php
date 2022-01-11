<?php

declare(strict_types=1);

namespace League\Flysystem\AdapterTestUtilities;

use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperationFailed;

class ExceptionThrowingFilesystemAdapter implements FilesystemAdapter
{
    /**
     * @var FilesystemAdapter
     */
    private $adapter;

    /**
     * @var array<string, FilesystemOperationFailed>
     */
    private $stagedExceptions = [];

    public function __construct(FilesystemAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function stageException(string $method, string $path, FilesystemOperationFailed $exception): void
    {
        $this->stagedExceptions[join('@', [$method, $path])] = $exception;
    }

    private function throwStagedException(string $method, $path): void
    {
        $method = preg_replace('~.+::~', '', $method);
        $key = join('@', [$method, $path]);

        if ( ! array_key_exists($key, $this->stagedExceptions)) {
            return;
        }

        $exception = $this->stagedExceptions[$key];
        unset($this->stagedExceptions[$key]);
        throw $exception;
    }

    public function fileExists(string $path): bool
    {
        $this->throwStagedException(__METHOD__, $path);

        return $this->adapter->fileExists($path);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->throwStagedException(__METHOD__, $path);

        $this->adapter->write($path, $contents, $config);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->throwStagedException(__METHOD__, $path);

        $this->adapter->writeStream($path, $contents, $config);
    }

    public function read(string $path): string
    {
        $this->throwStagedException(__METHOD__, $path);

        return $this->adapter->read($path);
    }

    public function readStream(string $path)
    {
        $this->throwStagedException(__METHOD__, $path);

        return $this->adapter->readStream($path);
    }

    public function delete(string $path): void
    {
        $this->throwStagedException(__METHOD__, $path);

        $this->adapter->delete($path);
    }

    public function deleteDirectory(string $path): void
    {
        $this->throwStagedException(__METHOD__, $path);

        $this->adapter->deleteDirectory($path);
    }

    public function createDirectory(string $path, Config $config): void
    {
        $this->throwStagedException(__METHOD__, $path);

        $this->adapter->createDirectory($path, $config);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $this->throwStagedException(__METHOD__, $path);

        $this->adapter->setVisibility($path, $visibility);
    }

    public function visibility(string $path): FileAttributes
    {
        $this->throwStagedException(__METHOD__, $path);

        return $this->adapter->visibility($path);
    }

    public function mimeType(string $path): FileAttributes
    {
        $this->throwStagedException(__METHOD__, $path);

        return $this->adapter->mimeType($path);
    }

    public function lastModified(string $path): FileAttributes
    {
        $this->throwStagedException(__METHOD__, $path);

        return $this->adapter->lastModified($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        $this->throwStagedException(__METHOD__, $path);

        return $this->adapter->fileSize($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $this->throwStagedException(__METHOD__, $path);

        return $this->adapter->listContents($path, $deep);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->throwStagedException(__METHOD__, $source);

        $this->adapter->move($source, $destination, $config);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->throwStagedException(__METHOD__, $source);

        $this->adapter->copy($source, $destination, $config);
    }

    public function directoryExists(string $path): bool
    {
        $this->throwStagedException(__METHOD__, $path);

        return $this->adapter->directoryExists($path);
    }
}
