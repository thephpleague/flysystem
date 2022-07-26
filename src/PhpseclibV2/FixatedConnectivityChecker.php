<?php

declare(strict_types=1);

namespace League\Flysystem\PhpseclibV2;

use phpseclib\Net\SFTP;

/**
 * @deprecated The "League\Flysystem\PhpseclibV2\FixatedConnectivityChecker" class is deprecated since Flysystem 3.0, use "League\Flysystem\PhpseclibV3\FixatedConnectivityChecker" instead.
 */
class FixatedConnectivityChecker implements ConnectivityChecker
{
    /**
     * @var int
     */
    private $succeedAfter;

    /**
     * @var int
     */
    private $numberOfTimesChecked = 0;

    public function __construct(int $succeedAfter = 0)
    {
        $this->succeedAfter = $succeedAfter;
    }

    public function isConnected(SFTP $connection): bool
    {
        if ($this->numberOfTimesChecked >= $this->succeedAfter) {
            return true;
        }

        $this->numberOfTimesChecked++;

        return false;
    }
}
