<?php

declare(strict_types=1);

namespace League\Flysystem;

class WhitespacePathNormalizer implements PathNormalizer
{
    public function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = $this->removeFunkyWhiteSpace($path);

        return $this->normalizeRelativePath($path);
    }

    private function removeFunkyWhiteSpace(string $path): string
    {
        // Remove unprintable characters and invalid unicode characters.
        // We do this check in a loop, since removing invalid unicode characters
        // can lead to new characters being created.
        while (preg_match('#\p{C}+|^\./#u', $path)) {
            $path = (string) preg_replace('#\p{C}+|^\./#u', '', $path);
        }

        return $path;
    }

    private function normalizeRelativePath(string $path): string
    {
        $parts = [];

        foreach (explode('/', $path) as $part) {
            switch ($part) {
                case '':
                case '.':
                    break;

                case '..':
                    if (empty($parts)) {
                        throw PathTraversalDetected::forPath($path);
                    }
                    array_pop($parts);
                    break;

                default:
                    $parts[] = $part;
                    break;
            }
        }

        return implode('/', $parts);
    }
}
