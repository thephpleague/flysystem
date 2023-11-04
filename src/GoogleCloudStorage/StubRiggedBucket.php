<?php

declare(strict_types=1);

namespace League\Flysystem\GoogleCloudStorage;

use Google\Cloud\Storage\Bucket;
use LogicException;
use Throwable;

class StubRiggedBucket extends Bucket
{
    private array $triggers = [];

    public function failForObject(string $name, ?Throwable $throwable = null): void
    {
        $this->setupTrigger('object', $name, $throwable);
    }

    public function failForUpload(string $name, ?Throwable $throwable = null): void
    {
        $this->setupTrigger('upload', $name, $throwable);
    }

    public function object($name, array $options = [])
    {
        $this->pushTrigger('object', $name);

        return parent::object($name, $options);
    }

    public function upload($data, array $options = [])
    {
        $this->pushTrigger('upload', $options['name'] ?? 'unknown-object-name');

        return parent::upload($data, $options);
    }

    private function setupTrigger(string $method, string $name, ?Throwable $throwable): void
    {
        $this->triggers[$method][$name] = $throwable ?? new LogicException('unknown error');
    }

    private function pushTrigger(string $method, string $name): void
    {
        $trigger = $this->triggers[$method][$name] ?? null;

        if ($trigger instanceof Throwable) {
            unset($this->triggers[$method][$name]);
            throw $trigger;
        }
    }
}
