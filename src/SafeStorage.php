<?php

namespace League\Flysystem;

/**
 * Prevents data from being leaked via exceptions and serialization.
 */
final class SafeStorage
{
    /**
     * @var string
     */
    protected $hash;

    public function __construct()
    {
        $this->hash = spl_object_hash($this);
    }

    /**
     * Stores a value in safe storage.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function storeSafely($key, $value)
    {
        $storage = &$this->getStorage();
        $storage[$key] = $value;
    }

    /**
     * Returns a value from safe storage.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function retrieveSafely($key)
    {
        $storage = &$this->getStorage();

        return isset($storage[$key]) ? $storage[$key] : null;
    }

    /**
     * Deletes this objects storage when the object is destroyed.
     */
    public function __destruct()
    {
        $storage = &$this->getStorage();
        unset($storage[$this->hash]);
    }

    /**
     * @return array
     */
    protected function &getStorage()
    {
        static $storage = [];

        if ( ! isset($storage[$this->hash])) {
            $storage[$this->hash] = [];
        }

        return $storage[$this->hash];
    }

    /**
     * Prevents SafeStorage from being serialized.
     */
    public function __sleep()
    {
        throw new \LogicException('SafeStorage cannot be serialized.');
    }
}
