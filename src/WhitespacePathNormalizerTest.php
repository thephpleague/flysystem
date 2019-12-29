<?php

declare(strict_types=1);

namespace League\Flysystem;

use PHPUnit\Framework\TestCase;

class WhitespacePathNormalizerTest extends TestCase
{
    /**
     * @var WhitespacePathNormalizer
     */
    private $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new WhitespacePathNormalizer();
    }
}
