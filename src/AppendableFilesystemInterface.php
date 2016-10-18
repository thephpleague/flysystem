<?php
namespace League\Flysystem;

use InvalidArgumentException;

interface AppendableFilesystemInterface extends FilesystemInterface
{
    /**
     * Create new or append existing file
     *
     * @param string   $path     The path of the file.
     * @param string   $contents The file contents.
     * @param array    $config   An optional configuration array.
     *
     * @throws FileNotFoundException
     *
     * @return bool True on success, false on failure.
     */
    public function append($path, $contents, array $config = []);

    /**
     * Create new or append existing file using stream
     *
     * @param string   $path     The path of the file.
     * @param resource $resource The file handle.
     * @param array    $config   An optional configuration array.
     *
     * @throws InvalidArgumentException If $resource is not a file handle.
     * @throws FileExistsException
     *
     * @return bool True on success, false on failure.
     */
    public function appendStream($path, $resource, array $config = []);
}
