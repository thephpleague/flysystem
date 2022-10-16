<?php

namespace League\Flysystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface ChecksumProvider
{
    /**
     * @return string MD5 hash of the file contents
     *
     * @throws UnableToGetChecksum
     */
    public function checksum(string $path, Config $config): string;
}
