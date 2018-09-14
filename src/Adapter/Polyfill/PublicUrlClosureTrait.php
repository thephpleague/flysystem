<?php


namespace League\Flysystem\Adapter\Polyfill;


use LogicException;

trait PublicUrlClosureTrait
{
    protected $hasPublicUrlClosure;
    protected $getPublicUrlClosure;

    /**
     * @param string $path
     * @return bool whether the path has a public URL.
     */
    public function hasPublicUrl($path)
    {
        return (!isset($this->hasPublicUrlClosure) && isset($this->getPublicUrlClosure))
            || (isset($this->hasPublicUrlClosure, $this->getPublicUrlClosure) && call_user_func($this->hasPublicUrlClosure, $path));
    }

    public function getPublicUrl($path)
    {
        if (!isset($this->getPublicUrlClosure)) {
            throw new LogicException(get_class($this) . ' does not support public URLs. Path: ' . $path);
        }
        return call_user_func($this->getPublicUrlClosure, $path);
    }

    protected function setHasPublicUrlClosure(\Closure $value)
    {
        $this->hasPublicUrlClosure = $value;
    }

    protected function setGetPublicUrlClosure(\Closure $value)
    {
        $this->getPublicUrlClosure = $value;
    }
}