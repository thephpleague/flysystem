<?php

declare(strict_types=1);

namespace League\Flysystem\Local;

interface VisibilityConverter
{
    public function forFile($visibility): int;
    public function forDirectory($visibility): int;
    public function inverseForFile($visibility);
    public function inverseForDirectory($visibility);
    public function defaultForDirectories(): int;
}
