<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV4;

use Closure;
use phpseclib4\Net\SFTP;
use RuntimeException;

/**
 * @internal This is only used for testing purposes.
 */
class SftpStub extends SFTP
{
    /**
     * @var array<string,bool>
     */
    private array $tripWires = [];

    public function failOnChmod(string $filename): void
    {
        $key = $this->formatTripKey('chmod', $filename);
        $this->tripWires[$key] = true;
    }

    public function chmod(string $filename, int $mode, bool $recursive = false): void
    {
        $key = $this->formatTripKey('chmod', $filename);
        $shouldTrip = $this->tripWires[$key] ?? false;

        if ($shouldTrip) {
            unset($this->tripWires[$key]);
            throw new RuntimeException('shouldTrip');
        }

        parent::chmod($filename, $mode, $recursive);
    }

    public function failOnPut(string $filename): void
    {
        $key = $this->formatTripKey('put', $filename);
        $this->tripWires[$key] = true;
    }

    /**
     * @param resource|string $data
     */
    public function put(
        string $remote_file,
        mixed $data,
        int $mode = self::SOURCE_STRING,
        int $start = -1,
        int $local_start = -1,
        ?Closure $progressCallback = null
    ): void {
        $key = $this->formatTripKey('put', $remote_file);
        $shouldTrip = $this->tripWires[$key] ?? false;

        if ($shouldTrip) {
            throw new RuntimeException('shouldTrip');
        }

        parent::put($remote_file, $data, $mode, $start, $local_start, $progressCallback);
    }

    /**
     * @param array<int,mixed> $arguments
     *
     * @return string
     */
    private function formatTripKey(...$arguments): string
    {
        $key = '';

        foreach ($arguments as $argument) {
            $key .= var_export($argument, true);
        }

        return $key;
    }

    public function resetTripWires(): void
    {
        $this->tripWires = [];
    }
}
