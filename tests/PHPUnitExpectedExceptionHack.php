<?php

trait PHPUnitExpectedExceptionHack
{
    public function setExpectedException($e, $message = '', $code = null)
    {
        if (method_exists($this, 'expectException')) {
            $this->expectException(is_object($e) ? get_class($e) : $e);
        } else {
            parent::setExpectedException($e, $message, $code);
        }
    }
}