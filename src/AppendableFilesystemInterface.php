<?php
namespace League\Flysystem;

use InvalidArgumentException;

interface AppendableFilesystemInterface extends FilesystemInterface
{
    /**
     * Append a file
     *
     * @param string   $path     The path of the new file.
     * @param string   $contents The file contents.
     * @param array    $config   An optional configuration array.
     *
     * @throws FileNotFoundException
     *
     * @return bool True on success, false on failure.
     */
    public function append($path, $contents, array $config = []);

    /**
     * Append a file using stream
     *
     * @param string   $path     The path of the new file.
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
