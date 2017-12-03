<?php

trait PHPUnitHacks
{
    /**
     * @param string $exception
     */
    public function expectException($exception)
    {
        if (is_callable('parent::expectException')) {
            return parent::expectException($exception);
        }

        parent::setExpectedException($exception);
    }

    /**
     * Returns a test double for the specified class.
     *
     * @param string $originalClassName
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     *
     * @throws PHPUnit_Framework_Exception
     */
    protected function createMock($originalClassName)
    {
        if (is_callable('parent::createMock')) {
            return parent::createMock($originalClassName);
        }

        return $this->getMock($originalClassName);
    }
}
