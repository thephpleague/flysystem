<?php

declare(strict_types=1);

namespace League\Flysystem\AdapterTestUtilities;

use const PHP_EOL;
use const STDOUT;
use League\Flysystem\FilesystemException;
use Throwable;

/**
 * @codeCoverageIgnore
 */
trait RetryOnTestException
{
    /**
     * @var string
     */
    protected $exceptionTypeToRetryOn;

    /**
     * @var int
     */
    protected $timeoutForExceptionRetry = 2;

    protected function retryOnException(string $className, int $timout = 2): void
    {
        $this->exceptionTypeToRetryOn = $className;
        $this->timeoutForExceptionRetry = $timout;
    }

    protected function retryScenarioOnException(string $className, callable $scenario, int $timeout = 2): void
    {
        $this->retryOnException($className, $timeout);
        $this->runScenario($scenario);
    }

    protected function dontRetryOnException(): void
    {
        $this->exceptionTypeToRetryOn = null;
    }

    /**
     * @internal
     *
     * @throws Throwable
     */
    protected function runSetup(callable $scenario): void
    {
        $previousException = $this->exceptionTypeToRetryOn;
        $previousTimeout = $this->timeoutForExceptionRetry;
        $this->retryOnException(FilesystemException::class);

        try {
            $this->runScenario($scenario);
        } finally {
            $this->exceptionTypeToRetryOn = $previousException;
            $this->timeoutForExceptionRetry = $previousTimeout;
        }
    }

    protected function runScenario(callable $scenario): void
    {
        if ($this->exceptionTypeToRetryOn === null) {
            $scenario();

            return;
        }

        $firstTryAt = \time();
        $lastTryAt = $firstTryAt + 60;

        while (time() <= $lastTryAt) {
            try {
                $scenario();

                return;
            } catch (Throwable $exception) {
                if ( ! $exception instanceof $this->exceptionTypeToRetryOn) {
                    throw $exception;
                }
                fwrite(STDOUT, 'Retrying ...' . PHP_EOL);
                sleep($this->timeoutForExceptionRetry);
            }
        }

        $this->exceptionTypeToRetryOn = null;

        if (isset($exception) && $exception instanceof Throwable) {
            throw $exception;
        }
    }
}
