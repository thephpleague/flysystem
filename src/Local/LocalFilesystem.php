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

use function chmod;
use function clearstatcache;
use function dirname;
use function error_clear_last;
use function error_get_last;
use function file_exists;
use function is_dir;

use function stream_copy_to_stream;

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
     * @var PathPrefixer
     */
    private $pathPrefixer;

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

    /**
     * @var LocalVisibilityInterpreting
     */
    private $visibility;

    public function __construct(
        string $location,
        LocalVisibilityInterpreting $visibilityHandler = null,
        int $writeFlags = LOCK_EX,
        int $linkHandling = self::DISALLOW_LINKS,
        string $directoryVisibility = Visibility::PUBLIC
    ) {
        $this->pathPrefixer = new PathPrefixer($location, DIRECTORY_SEPARATOR);
        $this->writeFlags = $writeFlags;
        $this->linkHandling = $linkHandling;
        $this->visibility = $visibilityHandler ?: new PublicAndPrivateVisibilityInterpreting();
        $this->ensureDirectoryExists($location, $this->visibility->defaultForDirectories());
        $this->directoryVisibility = $directoryVisibility;
    }

    public function write(string $location, string $contents, Config $config): void
    {
        $prefixedLocation = $this->pathPrefixer->prefixPath($location);
        $this->ensureDirectoryExists(
            dirname($prefixedLocation),
            $this->resolveDirectoryVisibility($config->get('directory_visibility'))
        );
        error_clear_last();

        if (($size = @file_put_contents($prefixedLocation, $contents, $this->writeFlags)) === false) {
            throw UnableToWriteFile::toLocation($location, error_get_last()['message'] ?? '');
        }

        if ($visibility = $config->get('visibility')) {
            $this->setVisibility($location, (string) $visibility);
        }
    }

    public function writeStream(string $location, $contents, Config $config): void
    {
        $path = $this->pathPrefixer->prefixPath($location);
        $this->ensureDirectoryExists(
            dirname($path),
            $this->resolveDirectoryVisibility($config->get('directory_visibility'))
        );

        $stream = fopen($path, 'w+b');

        if ( ! ($stream && stream_copy_to_stream($contents, $stream) && fclose($stream))) {
            throw UnableToWriteFile::toLocation($path);
        }

        if ($visibility = $config->get('visibility')) {
            $this->setVisibility($location, (string) $visibility);
        }
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

    protected function ensureDirectoryExists(string $dirname, int $visibility)
    {
        if ( ! is_dir($dirname)) {
            if ( ! @mkdir($dirname, $visibility, true)) {
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

    public function setVisibility(string $location, $visibility): void
    {
        $location = $this->pathPrefixer->prefixPath($location);
        $visibility = is_dir($location)
            ? $this->visibility->forDirectory($visibility)
            : $this->visibility->forFile($visibility);

        error_clear_last();
        if ( ! @chmod($location, $visibility)) {
            $extraMessage = error_get_last()['message'] ?? '';
            throw UnableToSetVisibility::atLocation($this->pathPrefixer->stripPrefix($location), $extraMessage);
        }
    }

    public function getVisibility(string $location, string $visibility): string
    {
    }

    private function resolveDirectoryVisibility($visibility)
    {
        return $visibility === null ? $this->visibility->defaultForDirectories() : $this->visibility->forDirectory(
            $visibility
        );
    }
}
