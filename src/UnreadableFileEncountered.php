<?php

declare(strict_types=1);

namespace League\Flysystem;

use RuntimeException;

class UnreadableFileEncountered extends RuntimeException implements FilesystemError
{
    /**
     * @var string
     */
    private $location;

    public function location(): string
    {
        return $this->location;
    }

    public static function atLocation(string $location)
    {
        $e = new static("Unreadable file encountered at location {$location}.");
        $e->location = $location;

        return $e;
    }
}
