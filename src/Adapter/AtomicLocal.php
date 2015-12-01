<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Config;
use League\Flysystem\Util;

class AtomicLocal extends Local
{
    /**
     * @inheritdoc
     **/
    public function write($path, $contents, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        $this->ensureDirectory(dirname($location));

        // First create the tmpfile
        $tmpFile = tempnam(sys_get_temp_dir(), $path);

        // Then write to that file
        if (($size = file_put_contents($tmpFile, $contents, $this->writeFlags)) === false) {
            return false;
        }

        $type = 'file';

        $result = compact('contents', 'type', 'size', 'path');

        if ($visibility = $config->get('visibility')) {
            $result['visibility'] = $visibility;
            // Set the correct permissions
            chmod($tmpFile, $this->permissionMap['file'][$visibility]);
        }

        // Then move the file
        if (rename($tmpFile, $location) == false) {
            return false;
        }

        return $result;
    }

    /**
     * @inheritdoc
     **/
    public function writeStream($path, $resource, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        $this->ensureDirectory(dirname($location));
        $tmpFileName = tempnam(sys_get_temp_dir(), basename($path));
        $stream = fopen($tmpFileName, 'w');

        if (!$stream) {
            return false;
        }

        stream_copy_to_stream($resource, $stream);

        if ($visibility = $config->get('visibility')) {
            chmod($tmpFileName, $this->permissionMap['file'][$visibility]);
        }

        if (!fclose($stream)) {
            return false;
        }

        if (!rename($tmpFileName, $location)) {
            return false;
        }

        return compact('path', 'visibility');
    }

    /**
     * @inheritdoc
     */
    public function update($path, $contents, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        $mimetype = Util::guessMimeType($path, $contents);
        $tmpFile = tempnam(sys_get_temp_dir(), $path);
        $size = file_put_contents($tmpFile, $contents, $this->writeFlags);

        if ($size === false) {
            return false;
        }

        if (!rename($tmpFile, $location)) {
            return false;
        }

        return compact('path', 'size', 'contents', 'mimetype');
    }
}
