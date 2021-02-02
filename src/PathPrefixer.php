<?php

declare(strict_types=1);

namespace League\Flysystem;

use function rtrim;
use function strlen;
use function substr;

final class PathPrefixer
{
    /**
     * @var string
     */
    private $prefix = '';

    /**
     * @var string
     */
    private $separator = '/';

    public function __construct(string $prefix, string $separator = '/')
    {
        $this->prefix = rtrim($prefix, '\\/');

        if ($this->prefix !== '' || $prefix === $separator) {
            $this->prefix .= $separator;
        }

        $this->separator = $separator;
    }

    public function prefixPath(string $path): string
    {
        return $this->prefix . ltrim($path, '\\/');
    }

    public function stripPrefix(string $path): string
    {
        /* @var string */
        return substr($path, strlen($this->prefix));
    }

    public function stripDirectoryPrefix(string $path): string
    {
        return rtrim($this->stripPrefix($path), '\\/');
    }

    public function prefixDirectoryPath(string $path): string
    {
        $prefixedPath = $this->prefixPath(rtrim($path, '\\/'));

        if ((substr($prefixedPath, -1) === $this->separator) || $prefixedPath === '') {
            return $prefixedPath;
        }

        return $prefixedPath . $this->separator;
    }
}
