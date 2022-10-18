<?php

namespace League\Flysystem\UrlGeneration;

use InvalidArgumentException;
use League\Flysystem\Config;
use League\Flysystem\PathPrefixer;

use function array_map;
use function count;
use function crc32;

final class ShardedPrefixPublicUrlGenerator implements PublicUrlGenerator
{
    /** @var PathPrefixer[] */
    private array $prefixers;
    private int $count;

    /**
     * @param string[] $prefixes
     */
    public function __construct(array $prefixes)
    {
        $this->count = count($prefixes);

        if ($this->count === 0) {
            throw new InvalidArgumentException('At least one prefix is required.');
        }

        $this->prefixers = array_map(static fn (string $prefix) => new PathPrefixer($prefix, '/'), $prefixes);
    }

    public function publicUrl(string $path, Config $config): string
    {
        $index = crc32($path) % $this->count;

        return $this->prefixers[$index]->prefixPath($path);
    }
}
