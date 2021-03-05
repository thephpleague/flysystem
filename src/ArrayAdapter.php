<?php

declare(strict_types=1);

namespace League\Flysystem;

use InvalidArgumentException;
use LogicException;

/**
 * Class ArrayAdapter.
 */
class ArrayAdapter implements FilesystemAdapter
{
    private $adapters;
    private $default;

    /**
     * ArrayAdapter constructor.
     *
     * @param FilesystemAdapter[] $adapters
     * @param string|null $default Name of default namespace
     */
    public function __construct(array $adapters, ?string $default = null)
    {
        $adapters = array_filter(
            $adapters,
            function ($adapter) {
                return $adapter instanceof FilesystemAdapter;
            }
        );

        $this->adapters = $adapters;
        $this->default = $default;
    }

    /**
     * Get adapter name.
     *
     * @param string $path
     *
     * @return string
     */
    protected function getAdapterName(string &$path): string
    {
        $matches = [];

        if (preg_match('#^@(?<namespace>\w+)(?<path>[/\\\].*)#i', $path, $matches) === 1) {
            $path = $matches['path'];

            return $matches['namespace'];
        }

        if (null === $this->default) {
            throw new LogicException('No default namespace defined');
        }

        return $this->default;
    }

    /**
     * Get adapter.
     *
     * @param string $path
     *
     * @return FilesystemAdapter
     */
    protected function getAdapter(string &$path): FilesystemAdapter
    {
        $name = $this->getAdapterName($path);

        if (false === array_key_exists($name, $this->adapters)) {
            throw new InvalidArgumentException(sprintf('Namespace "%s" does not exists', $name));
        }

        return $this->adapters[$name];
    }

    /**
     * @inheritDoc
     */
    public function fileExists(string $path): bool
    {
        return $this->getAdapter($path)->fileExists($path);
    }

    /**
     * @inheritDoc
     */
    public function write(string $path, string $contents, Config $config): void
    {
        $this->getAdapter($path)->write($path, $contents, $config);
    }

    /**
     * @inheritDoc
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->getAdapter($path)->writeStream($path, $contents, $config);
    }

    /**
     * @inheritDoc
     */
    public function read(string $path): string
    {
        return $this->getAdapter($path)->read($path);
    }

    /**
     * @inheritDoc
     */
    public function readStream(string $path)
    {
        return $this->getAdapter($path)->readStream($path);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $path): void
    {
        $this->getAdapter($path)->delete($path);
    }

    /**
     * @inheritDoc
     */
    public function deleteDirectory(string $path): void
    {
        $this->getAdapter($path)->deleteDirectory($path);
    }

    /**
     * @inheritDoc
     */
    public function createDirectory(string $path, Config $config): void
    {
        $this->getAdapter($path)->createDirectory($path, $config);
    }

    /**
     * @inheritDoc
     */
    public function setVisibility(string $path, string $visibility): void
    {
        $this->getAdapter($path)->setVisibility($path, $visibility);
    }

    /**
     * @inheritDoc
     */
    public function visibility(string $path): FileAttributes
    {
        return $this->getAdapter($path)->visibility($path);
    }

    /**
     * @inheritDoc
     */
    public function mimeType(string $path): FileAttributes
    {
        return $this->getAdapter($path)->mimeType($path);
    }

    /**
     * @inheritDoc
     */
    public function lastModified(string $path): FileAttributes
    {
        return $this->getAdapter($path)->lastModified($path);
    }

    /**
     * @inheritDoc
     */
    public function fileSize(string $path): FileAttributes
    {
        return $this->getAdapter($path)->fileSize($path);
    }

    /**
     * @inheritDoc
     */
    public function listContents(string $path, bool $deep): iterable
    {
        return $this->getAdapter($path)->listContents($path, $deep);
    }

    /**
     * @inheritDoc
     */
    public function move(string $source, string $destination, Config $config): void
    {
        $sourceAdapter = $this->getAdapter($source);
        $destinationAdapter = $this->getAdapter($destination);

        if ($sourceAdapter === $destinationAdapter) {
            $sourceAdapter->move($source, $destination, $config);
            return;
        }

        $destinationAdapter->writeStream($destination, $sourceAdapter->readStream($source), $config);
        $sourceAdapter->delete($source);
    }

    /**
     * @inheritDoc
     */
    public function copy(string $source, string $destination, Config $config): void
    {
        $sourceAdapter = $this->getAdapter($source);
        $destinationAdapter = $this->getAdapter($destination);

        if ($sourceAdapter === $destinationAdapter) {
            $sourceAdapter->move($source, $destination, $config);
            return;
        }

        $destinationAdapter->writeStream($destination, $sourceAdapter->readStream($source), $config);
    }
}
