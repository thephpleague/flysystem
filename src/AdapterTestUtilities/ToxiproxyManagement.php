<?php

declare(strict_types=1);

namespace League\Flysystem\AdapterTestUtilities;

use GuzzleHttp\Client;

/**
 * This class provides a client for the HTTP API provided by the proxy that simulates network issues.
 *
 * @see https://github.com/shopify/toxiproxy#http-api
 *
 * @phpstan-type RegisteredProxies 'ftp'|'sftp'|'ftpd'
 * @phpstan-type StreamDirection 'upstream'|'downstream'
 * @phpstan-type Type 'latency'|'bandwidth'|'slow_close'|'timeout'|'reset_peer'|'slicer'|'limit_data'
 * @phpstan-type Attributes array{latency?: int, jitter?: int, rate?: int, delay?: int}
 * @phpstan-type Toxic array{name?: string, type: Type, stream?: StreamDirection, toxicity?: float, attributes: Attributes}
 */
final class ToxiproxyManagement
{
    /** @var Client */
    private $apiClient;

    public function __construct(Client $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public static function forServer(string $apiUri = 'http://localhost:8474'): self
    {
        return new self(
            new Client(
                [
                    'base_uri' => $apiUri,
                    'base_url' => $apiUri, // Compatibility with older versions of Guzzle
                ]
            )
        );
    }

    public function removeAllToxics(): void
    {
        $this->apiClient->post('/reset');
    }

    /**
     * Simulates a peer reset on the client->server direction.
     *
     * @param RegisteredProxies $proxyName
     */
    public function resetPeerOnRequest(
        string $proxyName,
        int $timeoutInMilliseconds
    ): void {
        $configuration = [
            'type' => 'reset_peer',
            'stream' => 'upstream',
            'attributes' => ['timeout' => $timeoutInMilliseconds],
        ];

        $this->addToxic($proxyName, $configuration);
    }

    /**
     * Registers a network toxic for the given proxy.
     *
     * @param RegisteredProxies $proxyName
     * @param Toxic $configuration
     */
    private function addToxic(string $proxyName, array $configuration): void
    {
        $this->apiClient->post('/proxies/' . $proxyName . '/toxics', ['json' => $configuration]);
    }
}
