<?php

declare(strict_types=1);

namespace League\Flysystem\UrlGeneration;

use League\Flysystem\Config;
use League\Flysystem\PathPrefixer;

use function array_map;
use function array_values;
use function count;
use function fmod;
use function hash;
use function hexdec;
use function ltrim;
use function mb_substr;

class PrefixPublicUrlGenerator implements PublicUrlGenerator
{
    /** @var PathPrefixer[] */
    private array $prefixers;

    /**
     * @param string|string[] $urlPrefix
     */
    public function __construct(string|array $urlPrefix)
    {
        $this->prefixers = array_map(
            static fn (string $prefix) => new PathPrefixer($prefix),
            array_values((array) $urlPrefix)
        );
    }

    public function publicUrl(string $path, Config $config): string
    {
        if (1 === count($this->prefixers)) {
            return $this->prefixers[0]->prefixPath($path);
        }

        /**
         * @source https://github.com/symfony/symfony/blob/294195157c3690b869ff6295713a69ff38b3039c/src/Symfony/Component/Asset/UrlPackage.php#L115
         */
        $index = (int) fmod(hexdec(mb_substr(hash('sha256', ltrim($path, '/')), 0, 10)), count($this->prefixers));

        return $this->prefixers[$index]->prefixPath($path);
    }
}
