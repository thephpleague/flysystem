<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV3;

use phpseclib3\Net\SFTP;

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

    /**
     * @param int    $mode
     * @param string $filename
     * @param bool   $recursive
     *
     * @return bool|mixed
     */
    public function chmod($mode, $filename, $recursive = false)
    {
        $key = $this->formatTripKey('chmod', $filename);
        $shouldTrip = $this->tripWires[$key] ?? false;

        if ($shouldTrip) {
            unset($this->tripWires[$key]);

            return false;
        }

        return parent::chmod($mode, $filename, $recursive);
    }

    public function failOnPut(string $filename): void
    {
        $key = $this->formatTripKey('put', $filename);
        $this->tripWires[$key] = true;
    }

    /**
     * @param string          $remote_file
     * @param resource|string $data
     * @param int             $mode
     * @param int             $start
     * @param int             $local_start
     * @param null            $progressCallback
     *
     * @return bool
     */
    public function put(
        $remote_file,
        $data,
        $mode = self::SOURCE_STRING,
        $start = -1,
        $local_start = -1,
        $progressCallback = null
    ) {
        $key = $this->formatTripKey('put', $remote_file);
        $shouldTrip = $this->tripWires[$key] ?? false;

        if ($shouldTrip) {
            return false;
        }

        return parent::put($remote_file, $data, $mode, $start, $local_start, $progressCallback);
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

    public function reset(): void
    {
        $this->tripWires = [];
    }
}
