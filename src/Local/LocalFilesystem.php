<?php

declare(strict_types=1);

namespace League\Flysystem\Local;

use Generator;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;

use function array_replace_recursive;
use function clearstatcache;
use function dirname;
use function error_get_last;
use function file_exists;
use function is_dir;

use const DIRECTORY_SEPARATOR;
use const LOCK_EX;

class LocalFilesystem implements FilesystemAdapter
{
    /**
     * @var int
     */
    public const SKIP_LINKS = 0001;
    /**
     * @var int
     */
    public const DISALLOW_LINKS = 0002;
    /**
     * @var array
     */
    protected const PERMISSIONS = [
        'file' => [
            Visibility::PUBLIC  => 0644,
            Visibility::PRIVATE => 0600,
        ],
        'dir'  => [
            Visibility::PUBLIC  => 0755,
            Visibility::PRIVATE => 0700,
        ],
    ];

    /**
     * @var PathPrefixer
     */
    private $pathPrefixer;

    /**
     * @var array
     */
    private $permissions = [];

    /**
     * @var int
     */
    private $writeFlags;

    /**
     * @var int
     */
    private $linkHandling;

    /**
     * @var string
     */
    private $directoryVisibility;

    public function __construct(
        string $location,
        int $writeFlags = LOCK_EX,
        int $linkHandling = self::DISALLOW_LINKS,
        array $permissions = [],
        string $directoryVisibility = Visibility::PUBLIC
    ) {
        $this->pathPrefixer = new PathPrefixer($location, DIRECTORY_SEPARATOR);
        $this->permissions = array_replace_recursive(static::PERMISSIONS, $permissions);
        $this->writeFlags = $writeFlags;
        $this->linkHandling = $linkHandling;
        $this->ensureDirectoryExists($location, $directoryVisibility);
        $this->directoryVisibility = $directoryVisibility;
    }

    public function write(string $location, string $contents, Config $config): void
    {
        $prefixedLocation = $this->pathPrefixer->prefixPath($location);
        $this->ensureDirectoryExists(
            dirname($prefixedLocation),
            (string) $config->get('directory_visibility', $this->directoryVisibility)
        );
        error_clear_last();

        if (($size = @file_put_contents($prefixedLocation, $contents, $this->writeFlags)) === false) {
            throw UnableToWriteFile::toLocation($location, error_get_last()['message'] ?? '');
        }
    }

    public function writeStream(string $location, $contents, Config $config): void
    {
        $location = $this->pathPrefixer->prefixPath($location);
        $this->ensureDirectoryExists(
            dirname($location),
            (string) $config->get('directory_visibility', $this->directoryVisibility)
        );
    }

    public function update(string $location, string $contents, Config $config): void
    {
    }

    public function updateStream(string $location, $contents, Config $config): void
    {
    }

    public function delete(string $location): void
    {
    }

    public function deleteDirectory(string $prefix): void
    {
    }

    public function listContents(string $location): Generator
    {
    }

    public function rename(string $source, string $destination): void
    {
    }

    public function copy(string $source, string $destination): void
    {
    }

    public function read(string $location): string
    {
    }

    /**
     * @inheritDoc
     */
    public function readStream(string $location)
    {
    }

    protected function ensureDirectoryExists(string $dirname, string $visibility)
    {
        if ( ! is_dir($dirname)) {
            if ( ! @mkdir($dirname, $this->permissions['dir'][$visibility], true)) {
                $mkdirError = error_get_last();
            }
            clearstatcache(false, $dirname);
            if ( ! is_dir($dirname)) {
                $errorMessage = isset($mkdirError['message']) ? $mkdirError['message'] : '';
                throw UnableToCreateDirectory::atLocation($dirname, $errorMessage);
            }
        }
    }

    public function fileExists(string $location): bool
    {
        $location = $this->pathPrefixer->prefixPath($location);

        return file_exists($location);
    }

    public function createDirectory(string $location, Config $config): void
    {
    }

    public function setVisibility(string $location, string $visibility): void
    {
        $location = $this->pathPrefixer->prefixPath($location);
        $type = is_dir($location) ? 'dir' : 'file';

        if ( ! chmod($location, $this->permissions[$type][$visibility])) {
            throw UnableToSetVisibility::atLocation($this->pathPrefixer->stripPrefix($location));
        }
    }
}
