<?php

declare(strict_types=1);

namespace League\Flysystem;

class Config
{
    /**
     * @var array
     */
    private $values;

    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    public function get(string $property, $default = null)
    {
        return $this->values[$property] ?? $default;
    }

    public static function merge(Config ...$configs): Config
    {
        $values = [];
        foreach ($configs as $config) {
            $values[] = $config->values;
        }

        return new static(array_merge([], ...$values));
    }
}
