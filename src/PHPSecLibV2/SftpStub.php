<?php

declare(strict_types=1);

namespace League\Flysystem\PHPSecLibV2;

use phpseclib\Net\SFTP;

/**
 * @internal This is only used for testing purposes.
 */
class SftpStub extends SFTP
{
    private $tripWires = [];

    public function failOnChmod($filename): void
    {
        $key = $this->formatTripKey('chmod', $filename);
        $this->tripWires[$key] = true;
    }

    function chmod($mode, $filename, $recursive = false)
    {
        $key = $this->formatTripKey('chmod', $filename);
        $shouldTrip = $this->tripWires[$key] ?? false;

        if ($shouldTrip) {
            unset($this->tripWires[$key]);

            return false;
        }

        return parent::chmod($mode, $filename, $recursive);
    }

    public function failOnPut($filename): void
    {
        $key = $this->formatTripKey('put', $filename);
        $this->tripWires[$key] = true;
    }

    function put(
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

    private function formatTripKey(...$arguments): string
    {
        $key = '';

        foreach ($arguments as $argument) {
            $key .= var_export($argument, true);
        }

        return $key;
    }


}
