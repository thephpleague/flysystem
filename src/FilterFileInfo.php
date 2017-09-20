<?php

namespace League\Flysystem;

class FilterFileInfo
{
    const TYPE_FILE = 'file';
    const TYPE_DIR = 'dir';
    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_PRIVATE = 'private';

    /**
     * @var array
     */
    private $elements = [];

    /**
     * FilterFileInfo constructor.
     * @param string|null  $type
     * @param string|null  $path
     * @param integer|null $timestamp
     * @param integer|null $size
     * @param string|null  $extension
     * @param string|null  $filename
     * @param string|null  $visibility
     */
    public function __construct(
        $type,
        $path,
        $timestamp,
        $size,
        $extension,
        $filename,
        $visibility
    ) {
        if ( ! empty($type)) {
            $this->elements['type'] = $type;
        }
        if ( ! empty($path)) {
            $this->elements['path'] = $path;
        }
        if ( ! empty($timestamp)) {
            $this->elements['timestamp'] = $timestamp;
        }
        if ( ! empty($size)) {
            $this->elements['size'] = $size;
        }
        if ( ! empty($extension)) {
            $this->elements['extension'] = $extension;
        }
        if ( ! empty($filename)) {
            $this->elements['filename'] = $filename;
        }
        if ( ! empty($visibility)) {
            $this->elements['visibility'] = $visibility;
        }
    }

    /**
     * @param array $normalizedFileInfo
     *
     * @return FilterFileInfo
     */
    public static function createFromNormalized(array $normalizedFileInfo)
    {
        return (new FilterFileInfo(
            array_key_exists('type', $normalizedFileInfo) ? $normalizedFileInfo['type'] : null,
            array_key_exists('path', $normalizedFileInfo) ? $normalizedFileInfo['path'] : null,
            array_key_exists('timestamp', $normalizedFileInfo) ? $normalizedFileInfo['timestamp'] : null,
            array_key_exists('size', $normalizedFileInfo) ? $normalizedFileInfo['size'] : null,
            array_key_exists('extension', $normalizedFileInfo) ? $normalizedFileInfo['extension'] : null,
            array_key_exists('filename', $normalizedFileInfo) ? $normalizedFileInfo['filename'] : null,
            array_key_exists('visibility', $normalizedFileInfo) ? $normalizedFileInfo['visibility'] : null
        ));
    }

    /**
     * @return string
     *
     * @throws UnsupportedFilterException
     */
    public function getType()
    {
        if ( ! array_key_exists('type', $this->elements)) {
            throw new UnsupportedFilterException('Filtering by type is not supported.');
        }

        return $this->elements['type'];
    }

    /**
     * @return string
     *
     * @throws UnsupportedFilterException
     */
    public function getPath()
    {
        if ( ! array_key_exists('path', $this->elements)) {
            throw new UnsupportedFilterException('Filtering by path is not supported.');
        }

        return $this->elements['path'];
    }

    /**
     * @return int
     *
     * @throws UnsupportedFilterException
     */
    public function getTimestamp()
    {
        if ( ! array_key_exists('timestamp', $this->elements)) {
            throw new UnsupportedFilterException('Filtering by timestamp is not supported.');
        }

        return $this->elements['timestamp'];
    }

    /**
     * @return int
     *
     * @throws UnsupportedFilterException
     */
    public function getSize()
    {
        if ( ! array_key_exists('size', $this->elements)) {
            throw new UnsupportedFilterException('Filtering by size is not supported.');
        }

        return $this->elements['size'];
    }

    /**
     * @return string
     *
     * @throws UnsupportedFilterException
     */
    public function getExtension()
    {
        if ( ! array_key_exists('extension', $this->elements)) {
            throw new UnsupportedFilterException('Filtering by extension is not supported.');
        }

        return $this->elements['extension'];
    }

    /**
     * @return string
     *
     * @throws UnsupportedFilterException
     */
    public function getFilename()
    {
        if ( ! array_key_exists('filename', $this->elements)) {
            throw new UnsupportedFilterException('Filtering by filename is not supported.');
        }

        return $this->elements['filename'];
    }

    /**
     * @return string
     *
     * @throws UnsupportedFilterException
     */
    public function getVisibility()
    {
        if ( ! array_key_exists('visibility', $this->elements)) {
            throw new UnsupportedFilterException('Filtering by visibility is not supported.');
        }

        return $this->elements['visibility'];
    }
}
