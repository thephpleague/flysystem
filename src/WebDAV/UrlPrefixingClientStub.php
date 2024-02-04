<?php
declare(strict_types=1);

namespace League\Flysystem\WebDAV;

use Sabre\DAV\Client;

class UrlPrefixingClientStub extends Client
{
    /**
     * @param string $url
     */
    public function propFind($url, array $properties, $depth = 0): array
    {
        $response = parent::propFind($url, $properties, $depth);

        if ($depth === 0) {
            return $response;
        }

        $formatted = [];

        foreach ($response as $path => $object) {
            $formatted['https://domain.tld/' . ltrim($path, '/')] = $object;
        }

        return $formatted;
    }
}
