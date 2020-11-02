<?php

declare(strict_types=1);

namespace League\Flysystem\ZipArchive;

/**
 * @internal
 */
final class ZipArchivePathNormalizer
{
    /**
     * @var string
     */
    private $prefix;

    public function __construct(string $prefix)
    {
        $this->prefix = trim($prefix, '/');
    }

    public function forFile(string $path): string
    {
        $path = ltrim($path, '/');

        return $this->prefix === ''
            ? $path
            : $this->prefix . '/' . $path;
    }

    public function inverseForFile(string $path): string
    {
        return $this->prefix === ''
            ? $path
            : substr($path, strlen($this->prefix) + 1);
    }

    public function forDirectory(string $path): string
    {
        $path = trim($path, '/');
        if ($path === '') {
            return $this->prefix === ''
                ? ''
                : $this->prefix . '/';
        }

        return $this->prefix === ''
            ? $path . '/'
            : $this->prefix . '/' . $path . '/';
    }

    public function inverseForDirectory(string $path): string
    {
        $path = rtrim($path, '/');
        if ($path === '') {
            return '';
        }

        return $this->prefix === ''
            ? $path
            : substr($path, strlen($this->prefix) + 1);
    }
}
